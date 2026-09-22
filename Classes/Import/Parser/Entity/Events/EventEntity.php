<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\EventPlaceMatcher;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventCategoryMapper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventDateFactory;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventScheduleAdapter;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * Entity class for event imports.
 * Collected via ServiceLocator, so don't use a constructor
 */
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
    protected string $location = '';
    protected string $organizer = '';

    /**
     * Per-occurrence Date child entities. Pushed into the payload by the
     * Parser via getChildren(); each carries the parent's remote_id in the
     * 'event' transient bucket so the Resolver wires the FK back.
     *
     * @var list<DateEntity>
     */
    protected array $_dates = [];

    /**
     * The venue behind schema:location, when it arrives as an inline node.
     */
    protected ?LocationEntity $_location = null;

    /**
     * The organizer behind schema:organizer, when it arrives as an inline node.
     */
    protected ?OrganizerEntity $_organizer = null;

    /**
     * The place behind schema:location and schema:organizer, keyed by the
     * relation field it fills: a remote_id when the node is a bare reference,
     * otherwise the name and postal code the match rule needs.
     *
     * @var array<string, array<string, string>>
     */
    protected array $_placeReferences = [];

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        parent::parse($node, $language, $parserContext, $translationLanguages);

        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractValue($node['schema:name'] ?? null, $language);
        $this->details = $this->extractHtmlDescription($node['schema:description'] ?? null, $language);
        $this->web = $this->extractValue($node['schema:url'] ?? null, $language);
        $offers = is_array($node['schema:offers'] ?? null) ? $node['schema:offers'] : [];
        $this->ticket = $this->extractValue($offers['schema:url'] ?? null, $language);

        $this->recordMediaTransient(
            $node['schema:photo'] ?? null,
            $node['schema:image'] ?? null,
            $node['schema:video'] ?? null,
        );

        $this->_location = $this->buildLocation($node, $language);
        $this->location = $this->_location?->getGlobalId() ?? '';

        $this->_organizer = $this->buildOrganizer($node, $language);
        $this->organizer = $this->_organizer?->getGeneratedRemoteId() ?? '';

        $this->recordPlaceReferences($node, $language);

        $this->_dates = $this->buildDateRows($node, $parserContext);
        // Every route to zero dates converges here. An event
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
        $children = $this->_dates;

        if ($this->_location !== null) {
            $children[] = $this->_location;
        }

        if ($this->_organizer !== null) {
            $children[] = $this->_organizer;
        }

        return $children;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getPlaceReferences(): array
    {
        return $this->_placeReferences;
    }

    /**
     * A bare reference carries no address, so the venue cannot become a row of
     * its own; it names a place that is imported separately.
     *
     * @param array<string, mixed> $node
     */
    protected function recordPlaceReferences(array $node, string $language): void
    {
        $references = [];
        foreach ([
            EventPlaceMatcher::FIELD_HOSTS => 'schema:location',
            EventPlaceMatcher::FIELD_MANAGES => 'schema:organizer',
        ] as $field => $property) {
            $single = $this->singleNode($node, $property);
            if ($single === null) {
                continue;
            }

            if ($this->isBareReference($single)) {
                $id = $single['@id'] ?? null;
                if (is_string($id) && $id !== '') {
                    $references[$field] = ['remoteId' => $id];
                }
                continue;
            }

            $address = $single['schema:address'] ?? [];
            /** @var array<string, mixed> $address JSON-LD nodes are string-keyed. */
            $address = is_array($address) ? $address : [];

            $name = $this->extractValue($single['schema:name'] ?? null, $language);
            $postalCode = $this->extractValue($address['schema:postalCode'] ?? null, $language);
            if ($name === '' || $postalCode === '') {
                continue;
            }

            $references[$field] = ['name' => $name, 'postalCode' => $postalCode];
        }

        $this->_placeReferences = $references;
    }

    /**
     * The inline venue behind schema:location.
     *
     * @param array<string, mixed> $node
     */
    protected function buildLocation(array $node, string $language): ?LocationEntity
    {
        $locationNode = $this->singleNode($node, 'schema:location');
        if ($locationNode === null) {
            return null;
        }

        $entity = new LocationEntity();
        $entity->configure($locationNode, $language);

        return $entity->isValid() ? $entity : null;
    }

    /**
     * The inline organizer behind schema:organizer.
     *
     * @param array<string, mixed> $node
     */
    protected function buildOrganizer(array $node, string $language): ?OrganizerEntity
    {
        $organizerNode = $this->singleNode($node, 'schema:organizer');
        if ($organizerNode === null) {
            return null;
        }

        $entity = new OrganizerEntity();
        $entity->configure($organizerNode, $language);

        return $entity->isValid() ? $entity : null;
    }

    /**
     * Both relations are single-valued; where a list arrives the first node
     * wins.
     *
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>|null
     */
    protected function singleNode(array $node, string $property): ?array
    {
        $value = $node[$property] ?? null;
        if (!is_array($value) || $value === []) {
            return null;
        }

        $single = array_is_list($value) ? ($value[0] ?? null) : $value;
        if (!is_array($single) || $single === []) {
            return null;
        }

        /** @var array<string, mixed> $single JSON-LD nodes are string-keyed. */
        return $single;
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
            return $this->extractValue($item['schema:value'] ?? null, $language);
        }
        return '';
    }
}
