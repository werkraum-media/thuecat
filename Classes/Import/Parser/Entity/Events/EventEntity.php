<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\Events\Service\DestinationDataImportService\DatesFactory;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventCategoryMapper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventScheduleAdapter;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\StubImport;
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
// Date wiring delegates to ext:events' DatesFactory: it expands recurring
// schedules into per-occurrence rows and filters past dates by the
// TYPO3 Context date aspect. EventScheduleAdapter shapes JSON-LD
// schema:Schedule nodes into the array form DatesFactory consumes; StubImport
// stands in for DatesFactory's Import dependency (it only reads
// getRepeatUntil()).
//
// Collaborators (DatesFactory, EventScheduleAdapter) are resolved via
// GeneralUtility::makeInstance rather than constructor injection: the Parser
// instantiates entities through a ServiceLocator that does not supply
// arguments, so constructor DI is not available. makeInstance is consistent
// with how the abstract resolves core singletons elsewhere.
class EventEntity extends AbstractEventsEntity
{
    public string $table = 'tx_events_domain_model_event';

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
     * Read by the resolver to wire the category relation; not a DB column.
     *
     * @var list<array{remoteId: string, title: string}>
     */
    protected array $_categories = [];

    /**
     * Feeds the import report; not a DB column.
     *
     * @var list<array{kind: string, sourcePrefix: string, matched: array<string, string>, unmatched: list<string>}>
     */
    protected array $_matchReports = [];

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        parent::parse($node, $language, $parserContext, $translationLanguages);

        $this->translations = [];

        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->details = $this->extractHtmlDescription($node['schema:description'] ?? null, $language);
        $this->web = $this->extractTypedValue($node['schema:url'] ?? null);
        $offers = is_array($node['schema:offers'] ?? null) ? $node['schema:offers'] : [];
        $this->ticket = $this->extractTypedValue($offers['schema:url'] ?? null);

        $this->_dates = $this->buildDateRows($node['schema:eventSchedule'] ?? null);

        $types = $node['@type'] ?? [];
        $types = is_array($types) ? array_values(array_filter($types, 'is_string')) : [];
        $mapper = new EventCategoryMapper();
        $this->_categories = array_map(
            static fn (array $category): array => [
                'remoteId' => $mapper->prefixed($category['remoteId']),
                'title' => $category['title'],
            ],
            $mapper->categoriesFor($types)
        );

        $report = $mapper->reportMatchStatus($types);
        $this->_matchReports = [
            [
                'kind' => $mapper->kind(),
                'sourcePrefix' => $mapper->sourcePrefix(),
                'matched' => $report['matched'],
                'unmatched' => $report['unmatched'],
            ],
        ];
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
     * @return list<array{remoteId: string, title: string}>
     */
    public function getCategories(): array
    {
        return $this->_categories;
    }

    /**
     * @return list<array{kind: string, sourcePrefix: string, matched: array<string, string>, unmatched: list<string>}>
     */
    public function getMatchReports(): array
    {
        return $this->_matchReports;
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        unset($array['_dates'], $array['_categories'], $array['_matchReports']);
        return $array;
    }

    /**
     * Hand each schema:Schedule node to DatesFactory via the adapter, then
     * wrap each per-occurrence Date model in a DateEntity child so the Parser
     * can flush them into the payload after the parent.
     *
     * Past-date filtering is owned by DatesFactory (it consults the TYPO3
     * Context date aspect — tests pin it via setDateAspect()).
     *
     * @return list<DateEntity>
     */
    private function buildDateRows(mixed $schedule): array
    {
        $adapter = GeneralUtility::makeInstance(EventScheduleAdapter::class);
        $intervals = $adapter->toTimeIntervals($schedule);
        if ($intervals === []) {
            return [];
        }

        // StubImport's getRepeatUntil() is consulted only when a recurring
        // schedule omits its own repeatUntil/repeatCount. Distel's Monthly
        // block carries schema:endDate, so the stub default never fires for
        // current fixtures — it's a safety net.
        $datesFactory = GeneralUtility::makeInstance(DatesFactory::class);
        $children = [];
        foreach ($datesFactory->createDates(new StubImport(), $intervals, false) as $date) {
            $start = $date->getStart();
            $end = $date->getEnd();
            $entity = new DateEntity();
            $entity->configure(
                $this->remote_id,
                $start->format('c'),
                ($end ?? $start)->format('c'),
                // Date::getCanceled() returns 'canceled' or 'no'; DateEntity
                // mirrors that string shape into its own `canceled` column.
                $date->getCanceled() === 'canceled'
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
