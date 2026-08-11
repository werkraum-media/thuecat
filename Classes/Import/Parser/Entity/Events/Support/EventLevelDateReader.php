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
use DateTimeZone;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// Dates published on the event node, for events carrying no schema:eventSchedule.
//
// Separate from EventScheduleAdapter because the two disagree on schema:endDate:
// in a Schedule it bounds the repetition, here the occurrence. Keeping a schedule
// node out of this class and an event node out of toInterval() (whose
// `startTime ?? startDate` would misread it) makes either reading unreachable
// from the other.
#[Autoconfigure(public: true)]
final class EventLevelDateReader
{
    // Matches EventScheduleAdapter::toInterval()'s fallback, so both paths read
    // an offsetless value in the same zone.
    private const DEFAULT_TIMEZONE = 'Europe/Berlin';

    /**
     * @param array<string, mixed> $node
     */
    public function toOccurrence(array $node): ?EventOccurrence
    {
        $start = $this->toDateTime($node['schema:startDate'] ?? null, '00:00');
        $end = $this->toDateTime($node['schema:endDate'] ?? null, '23:59');

        // Both bounds or none: substituting the present one for the absent one
        // would invent an occurrence upstream never published.
        if ($start === null || $end === null) {
            return null;
        }

        return new EventOccurrence($start->format('c'), $end->format('c'));
    }

    /**
     * A date-only value denotes a full day, so it takes the bound's edge of
     * that day. 00:00/23:59 is ext:events' own all-day convention — see
     * Date::getHasUsefulStartTime()/getHasUsefulEndTime(), which render such a
     * date without times.
     *
     * Such a value carries no offset either, so it is read in DEFAULT_TIMEZONE
     * rather than PHP's default — the data is German, and the schedule path
     * assumes the same zone.
     *
     * Judged on the value, never on the sibling @type: one node read fewer,
     * and a declaration cannot contradict the string it describes.
     */
    private function toDateTime(mixed $value, string $timeForDateOnly): ?DateTimeImmutable
    {
        if (!is_array($value)) {
            return null;
        }
        $raw = $value['@value'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $timezone = null;
        if (!str_contains($raw, 'T')) {
            $raw .= 'T' . $timeForDateOnly . ':00';
            $timezone = new DateTimeZone(self::DEFAULT_TIMEZONE);
        }

        try {
            return new DateTimeImmutable($raw, $timezone);
        } catch (Exception) {
            return null;
        }
    }
}
