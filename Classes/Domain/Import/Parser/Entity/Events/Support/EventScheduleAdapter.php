<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\Events\Support;

// Translate one or many schema:Schedule JSON-LD nodes into the array shape
// WerkraumMedia\Events\Service\DestinationDataImportService\DatesFactory expects:
//   single:    ['start' => ISO, 'end' => ISO, 'tz' => 'Europe/Berlin']
//   recurring: ['start' => ISO, 'end' => ISO, 'tz' => 'Europe/Berlin',
//                'freq' => 'Daily'|'Weekly'|'Monthly',
//                'weekdays' => list<string>,        // Weekly only
//                'weekday'  => string,              // Monthly only
//                'dayOrdinal' => int,               // Monthly only (1..5)
//                'repeatUntil' => ISO]              // optional; falls back to Import::getRepeatUntil()
//
// Recurring schedules carry schema:frequency / schema:repeatFrequency. Single
// schedules omit them. We surface this distinction by simply omitting `freq`
// from the output map for singles — that's the same shape DatesFactory's
// `Date::isSingle()` checks for.
//
// HEADS-UP / FALLBACK POLICY (revisit later):
// When the JSON-LD node has no schema:endTime (and only schema:endDate, which
// is date-only and for recurring blocks denotes the series end, not the
// per-occurrence end) we mirror `start` into `end`. That gives
// zero-duration occurrences. The frontend will show only the start time.
// Two reasons for this choice:
//   - It avoids fabricating a duration we don't have evidence for.
//   - It keeps single + recurring behaviour symmetric.
// Replace the policy in $this->resolveOccurrenceEnd() if a better signal
// surfaces (e.g. a sibling Schedule's typical duration, a default offset, or
// rejecting the schedule outright).
final class EventScheduleAdapter
{
    /**
     * @return list<array<string, mixed>>
     */
    public function toTimeIntervals(mixed $schedule): array
    {
        if ($schedule === null) {
            return [];
        }
        $items = is_array($schedule) && array_is_list($schedule) ? $schedule : [$schedule];

        $intervals = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $interval = $this->toInterval($item);
            if ($interval === null) {
                continue;
            }
            $intervals[] = $interval;
        }

        return $intervals;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function toInterval(array $node): ?array
    {
        $start = $this->extractTypedValue($node['schema:startTime'] ?? $node['schema:startDate'] ?? null);
        if ($start === '') {
            return null;
        }

        $end = $this->resolveOccurrenceEnd($node, $start);
        $tz = $this->extractTypedValue($node['schema:scheduleTimezone'] ?? null);
        if ($tz === '') {
            $tz = 'Europe/Berlin';
        }

        $interval = [
            'start' => $start,
            'end' => $end,
            'tz' => $tz,
        ];

        $freq = $this->resolveFrequency($node);
        if ($freq === null) {
            return $interval;
        }

        $interval['freq'] = $freq;
        if ($freq === 'Weekly') {
            $interval['weekdays'] = $this->resolveWeekdays($node);
        }
        if ($freq === 'Monthly') {
            $interval['weekday'] = $this->resolveWeekdays($node)[0] ?? '';
            $ordinal = $this->extractTypedValue($node['schema:byMonthWeek'] ?? null);
            $interval['dayOrdinal'] = $ordinal === '' ? 0 : (int)$ordinal;
        }

        $repeatUntil = $this->extractTypedValue($node['schema:endDate'] ?? null);
        if ($repeatUntil !== '') {
            $interval['repeatUntil'] = $repeatUntil;
        }

        return $interval;
    }

    /**
     * Per-occurrence end. Today's policy: prefer schema:endTime; otherwise
     * mirror start. See class docblock for the FALLBACK POLICY discussion.
     *
     * Note we deliberately do NOT use schema:endDate here for recurring
     * schedules — endDate is the series-end date, used as repeatUntil. For
     * singles, endDate without endTime would still be date-only (midnight),
     * which is misleading for an evening event.
     */
    private function resolveOccurrenceEnd(array $node, string $start): string
    {
        $end = $this->extractTypedValue($node['schema:endTime'] ?? null);
        if ($end !== '') {
            return $end;
        }
        // FALLBACK: no per-occurrence end → mirror start. Change here if a
        // better source becomes available.
        return $start;
    }

    /**
     * Map schema:frequency / schema:repeatFrequency duration codes to the
     * factory's named frequencies. Returns null for non-recurring schedules.
     */
    private function resolveFrequency(array $node): ?string
    {
        $freq = $this->extractTypedValue(
            $node['schema:frequency'] ?? $node['schema:repeatFrequency'] ?? null
        );
        if ($freq === '') {
            return null;
        }
        return match ($freq) {
            'P1D' => 'Daily',
            'P1W' => 'Weekly',
            'P1M' => 'Monthly',
            default => null,
        };
    }

    /**
     * schema:byDay is a single typed @value or list of typed @values, each
     * `schema:Sunday`/`schema:Monday`/...; the factory expects bare names.
     *
     * @return list<string>
     */
    private function resolveWeekdays(array $node): array
    {
        $value = $node['schema:byDay'] ?? null;
        if ($value === null) {
            return [];
        }
        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $names = [];
        foreach ($items as $item) {
            $raw = $this->extractTypedValue($item);
            if ($raw === '') {
                continue;
            }
            $colon = strrpos($raw, ':');
            $names[] = $colon === false ? $raw : substr($raw, $colon + 1);
        }
        return $names;
    }

    private function extractTypedValue(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }
        $typedValue = $value['@value'] ?? '';
        if (!is_string($typedValue) && !is_int($typedValue) && !is_float($typedValue) && !is_bool($typedValue)) {
            return '';
        }
        return (string)$typedValue;
    }
}
