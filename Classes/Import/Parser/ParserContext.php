<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser;

class ParserContext
{
    /**
     * Parsing has no logger; collected here and flushed after the run.
     *
     * @var array<string, list<string>> event remote_id => day values that could not seed a series
     */
    public array $unusableScheduleDays = [];

    /**
     * @var array<string, list<string>> event remote_id => usable weekdays the import could not carry
     */
    public array $droppedScheduleDays = [];

    /**
     * @var array<string, string> event remote_id => title, for events that ended up with no date at all
     */
    public array $eventsWithoutDates = [];

    /**
     * @var array<string, string> event remote_id => title, for events publishing event-level date keys that could not be resolved
     */
    public array $unresolvableEventDates = [];

    public function __construct(
        public readonly int $importConfigurationUid,
        public readonly string $apiDomain = '',
    ) {
    }
}
