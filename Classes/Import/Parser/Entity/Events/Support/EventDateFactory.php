<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use WerkraumMedia\Events\Service\DestinationDataImportService\DatesFactory;

// The one place EXT:thuecat builds event dates, wrapping the ext:events
// dependency instead of reaching into it from the entity.
//
// Routing is decided on the PRESENCE of schema:eventSchedule, never on whether
// it yields occurrences: in the mixed shape the event-level pair is that
// schedule's min-start/max-end envelope, so falling back to it would replace a
// whole series with one long occurrence.
#[Autoconfigure(public: true)]
final class EventDateFactory
{
    private bool $unresolvableEventLevelDate = false;

    public function __construct(
        private readonly EventScheduleAdapter $scheduleAdapter,
        private readonly EventLevelDateReader $eventLevelReader,
        private readonly DatesFactory $datesFactory,
        private readonly Context $context,
    ) {
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return list<EventOccurrence>
     */
    public function toOccurrences(array $node): array
    {
        $this->unresolvableEventLevelDate = false;

        if (($node['schema:eventSchedule'] ?? null) !== null) {
            return $this->fromSchedule($node['schema:eventSchedule']);
        }

        return $this->fromEventLevel($node);
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return list<EventOccurrence>
     */
    private function fromEventLevel(array $node): array
    {
        $occurrence = $this->eventLevelReader->toOccurrence($node);
        if ($occurrence instanceof EventOccurrence) {
            return $this->isPast($occurrence) ? [] : [$occurrence];
        }

        // Only a node that published date keys has an unresolvable date; one
        // publishing none is simply dateless, which a different entry reports.
        $publishedDateKeys = ($node['schema:startDate'] ?? null) !== null
            || ($node['schema:endDate'] ?? null) !== null;
        $this->unresolvableEventLevelDate = $publishedDateKeys;

        return [];
    }

    /**
     * @return list<EventOccurrence>
     */
    private function fromSchedule(mixed $schedule): array
    {
        $intervals = $this->scheduleAdapter->toTimeIntervals($schedule);
        if ($intervals === []) {
            return [];
        }
        $excludedDates = $this->scheduleAdapter->toExcludedDates($schedule);

        $occurrences = [];
        foreach ($this->datesFactory->createDates(new StubImport(), $intervals, false) as $date) {
            $start = $date->getStart();
            $end = $date->getEnd();
            // Excepted dates are date-only, occurrences carry a time — compare
            // the calendar day, not the instant.
            if (in_array($start->format('Y-m-d'), $excludedDates, true)) {
                continue;
            }
            $occurrences[] = new EventOccurrence(
                $start->format('c'),
                ($end ?? $start)->format('c'),
                // Date::getCanceled() returns 'canceled' or 'no'.
                $date->getCanceled() === 'canceled'
            );
        }

        return $occurrences;
    }

    /**
     * Mirrors the rule DatesFactory applies to a SINGLE date — strictly after
     * midnight today, judged on the start. Its recurring paths use a different
     * comparison; an event-level occurrence is a single date, so this is the
     * branch its data would have reached.
     */
    private function isPast(EventOccurrence $occurrence): bool
    {
        $today = $this->context->getPropertyFromAspect('date', 'full', new DateTimeImmutable());
        if (!$today instanceof DateTimeImmutable) {
            $today = new DateTimeImmutable();
        }

        return new DateTimeImmutable($occurrence->start) <= $today->modify('midnight');
    }

    /**
     * Whether the last call found event-level date keys it could not resolve.
     * Distinct from "no dates at all", which is not a date-level failure.
     */
    public function hadUnresolvableEventLevelDate(): bool
    {
        return $this->unresolvableEventLevelDate;
    }
}
