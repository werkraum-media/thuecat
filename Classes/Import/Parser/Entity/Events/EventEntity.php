<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventCategoryMapper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventDateFactory;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventScheduleAdapter;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

// Writes into ext:events tables. v1: bare event row plus expanded date rows
// for both single and recurring schedules. Nested location/organizer rows
// still pending — they land in a follow-up that emits child entities
// alongside the parent and wires FKs via the existing transient/Resolver path.
//
// `remote_id` is the JSON-LD @id and is the key the ThueCat Resolver uses to
// look up existing rows for upsert. ext:events tables get a `remote_id` column
// via TCA override + ext_tables.sql in this extension. ext:events' native
// `global_id` (sha256 of address parts on Location) is untouched here — that
// concept belongs to ext:events' own importer and to nested Location rows.
//
// Date wiring goes through EventDateFactory, which owns both shapes — a
// schedule (delegated to ext:events' DatesFactory) and dates on the event node
// itself — and decides between them. EventScheduleAdapter is still consulted
// here for the schedule DIAGNOSTICS, which are about the schedule value rather
// than about the dates built from it.
//
// Collaborators are resolved via
// GeneralUtility::makeInstance rather than constructor injection: the Parser
// instantiates entities through a ServiceLocator that does not supply
// arguments, so constructor DI is not available. makeInstance is consistent
// with how the abstract resolves core singletons elsewhere.
class EventEntity extends AbstractEventsEntity
{
    public const TABLE = 'tx_events_domain_model_event';

    // ext:events already uses `keywords` for the plain-string field its own
    // importer fills.
    public const KEYWORD_FIELD = 'keywords_relation';

    // One field for every slot: events rank images but do not single one out.
    public const MEDIA_FIELDS = [
        'photo' => 'images',
        'image' => 'images',
    ];

    protected string $remote_id = '';
    protected string $title = '';
    protected string $details = '';
    protected string $web = '';
    protected string $ticket = '';

    /**
     * Per-occurrence Date child entities. Pushed into the payload by the
     * Parser via getChildren(); each carries the parent's remote_id in the
     * 'event' transient bucket so the Resolver wires the FK back.
     *
     * @var list<DateEntity>
     */
    protected array $_dates = [];

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        parent::parse($node, $language, $parserContext, $translationLanguages);

        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->details = $this->extractHtmlDescription($node['schema:description'] ?? null, $language);
        $this->web = $this->extractTypedValue($node['schema:url'] ?? null);
        $offers = is_array($node['schema:offers'] ?? null) ? $node['schema:offers'] : [];
        $this->ticket = $this->extractTypedValue($offers['schema:url'] ?? null);

        $this->recordMediaTransient(
            $node['schema:photo'] ?? null,
            $node['schema:image'] ?? null,
            $node['schema:video'] ?? null,
        );

        $this->_dates = $this->buildDateRows($node, $parserContext);
        // Every route to zero dates converges here: no schedule, no usable day,
        // all excepted, all past, no resolvable event-level date. An event
        // without dates cannot be displayed.
        if ($this->_dates === []) {
            $parserContext->eventsWithoutDates[$this->remote_id] = $this->title;
        }

        $this->applyCategoryMapper(new EventCategoryMapper(), $node);
        $this->recordKeywords($node);
    }

    public function handlesTypes(): array
    {
        return [
            'schema:Event',
        ];
    }

    /**
     * Flat-row view used by tests and any caller that wants the per-occurrence
     * data without poking at child entity internals. Mirrors the columns
     * DateEntity emits (minus its synthetic remote_id).
     *
     * @return list<array<string, string|int|float>>
     */
    public function getDates(): array
    {
        return array_map(static function (DateEntity $entity): array {
            $row = $entity->toArray();
            unset($row['remote_id']);
            return $row;
        }, $this->_dates);
    }

    /**
     * @return list<EntityInterface>
     */
    public function getChildren(): array
    {
        return $this->_dates;
    }

    /**
     * Wrap each occurrence the factory yields in a DateEntity child so the
     * Parser can flush them into the payload after the parent. Both date
     * shapes converge here, so nothing downstream can tell them apart.
     *
     * @param array<string, mixed> $node
     *
     * @return list<DateEntity>
     */
    private function buildDateRows(array $node, ParserContext $parserContext): array
    {
        $schedule = $node['schema:eventSchedule'] ?? null;
        $adapter = GeneralUtility::makeInstance(EventScheduleAdapter::class);

        $unusableDays = $adapter->toUnusableDays($schedule);
        if ($unusableDays !== []) {
            $parserContext->unusableScheduleDays[$this->remote_id] = $unusableDays;
        }
        $droppedDays = $adapter->toDroppedDays($schedule);
        if ($droppedDays !== []) {
            $parserContext->droppedScheduleDays[$this->remote_id] = $droppedDays;
        }

        $factory = GeneralUtility::makeInstance(EventDateFactory::class);
        $occurrences = $factory->toOccurrences($node);

        if ($factory->hadUnresolvableEventLevelDate()) {
            $parserContext->unresolvableEventDates[$this->remote_id] = $this->title;
        }

        $children = [];
        foreach ($occurrences as $occurrence) {
            $entity = new DateEntity();
            $entity->configure(
                $this->remote_id,
                $occurrence->start,
                $occurrence->end,
                $occurrence->canceled
            );
            $children[] = $entity;
        }
        return $children;
    }

    /**
     * Pick the @value of the JSON-LD entry whose @type is thuecat:Html.
     * schema:description carries plain + HTML siblings; we want the HTML one
     * for the richtext `details` column.
     */
    private function extractHtmlDescription(mixed $value, string $language): string
    {
        if (!is_array($value)) {
            return '';
        }
        $items = array_is_list($value) ? $value : [$value];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $types = (array)($item['@type'] ?? []);
            if (!in_array('thuecat:Html', $types, true)) {
                continue;
            }
            return $this->extractLocalisedValue($item['schema:value'] ?? null, $language);
        }
        return '';
    }

    /**
     * Read a single-typed @value(URLs, dates). Distinct from
     * extractLocalisedValue: typed @values have no @language.
     */
    private function extractTypedValue(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }
        $raw = $value['@value'] ?? null;
        if (is_string($raw) || is_int($raw) || is_float($raw)) {
            return (string)$raw;
        }
        return '';
    }
}
