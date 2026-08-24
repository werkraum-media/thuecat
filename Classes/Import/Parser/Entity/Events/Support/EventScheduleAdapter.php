<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\LocalisedValueReader;

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
#[Autoconfigure(public: true)]
final class EventScheduleAdapter
{
    /**
     * The only day values a date series can be seeded from.
     */
    private const WEEKDAY_NAMES = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function toTimeIntervals(mixed $schedule): array
    {
        $intervals = [];
        foreach ($this->scheduleNodes($schedule) as $node) {
            $interval = $this->toInterval($node);
            if ($interval === null) {
                continue;
            }
            $intervals[] = $interval;
        }

        return $intervals;
    }

    /**
     * Dates the schedule excludes, as Y-m-d. Kept out of the interval map so the
     * DatesFactory contract stays as it is; the caller drops the occurrences.
     *
     * @return list<string>
     */
    public function toExcludedDates(mixed $schedule): array
    {
        $excluded = [];
        foreach ($this->scheduleNodes($schedule) as $node) {
            $value = $node['schema:exceptDate'] ?? null;
            if ($value === null) {
                continue;
            }
            $values = is_array($value) && array_is_list($value) ? $value : [$value];
            foreach ($values as $entry) {
                $raw = $this->extractTypedValue($entry);
                if ($raw === '') {
                    continue;
                }
                $excluded[$raw] = true;
            }
        }

        return array_keys($excluded);
    }

    /**
     * Day values that name no weekday, so cannot seed a series.
     *
     * @return list<string>
     */
    public function toUnusableDays(mixed $schedule): array
    {
        $unusable = [];
        foreach ($this->scheduleNodes($schedule) as $node) {
            foreach ($this->dayNames($node) as $name) {
                if (!in_array($name, self::WEEKDAY_NAMES, true)) {
                    $unusable[$name] = true;
                }
            }
        }

        return array_keys($unusable);
    }

    /**
     * Usable weekdays beyond the one a monthly schedule can carry. Weekly
     * carries every day, so it never drops any.
     *
     * @return list<string>
     */
    public function toDroppedDays(mixed $schedule): array
    {
        $dropped = [];
        foreach ($this->scheduleNodes($schedule) as $node) {
            if ($this->resolveFrequency($node) !== 'Monthly') {
                continue;
            }
            $usable = $this->resolveWeekdays($node);
            foreach (array_slice($usable, 1) as $name) {
                $dropped[$name] = true;
            }
        }

        return array_keys($dropped);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduleNodes(mixed $schedule): array
    {
        if ($schedule === null) {
            return [];
        }
        $items = is_array($schedule) && array_is_list($schedule) ? $schedule : [$schedule];

        $nodes = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                /** @var array<string, mixed> $item JSON-LD nodes are string-keyed. */
                $nodes[] = $item;
            }
        }
        return $nodes;
    }

    /**
     * Bare day names as given, with no usability filtering.
     *
     * @param array<string, mixed> $node
     *
     * @return list<string>
     */
    private function dayNames(array $node): array
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

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>|null
     */
    private function toInterval(array $node): ?array
    {
        // SCHEDULE nodes only. An event node carries the same key names with
        // opposite meaning — its schema:endDate bounds one occurrence, not the
        // series — so feeding one here reads it as repeatUntil below. Event
        // level dates belong to EventLevelDateReader.
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
            // Already filtered to weekday names, so the first is usable — the
            // point of taking it here is that position no longer decides.
            // A monthly series carries one day; any further ones are dropped.
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
     * Values that name no weekday are dropped: schema:PublicHolidays is valid
     * upstream but cannot seed a date series, and the factory expands a day by
     * feeding it to DateTimeImmutable::modify(). Matched against the closed set
     * rather than probing modify(), which also accepts "+1 day" and "now".
     *
     * @param array<string, mixed> $node
     *
     * @return list<string>
     */
    private function resolveWeekdays(array $node): array
    {
        return array_values(array_filter(
            $this->dayNames($node),
            static fn (string $name): bool => in_array($name, self::WEEKDAY_NAMES, true)
        ));
    }

    /**
     * Schedules carry only untagged typed @values — times, dates, timezones —
     * so every read lands on the reader's fallback branch and no language is
     * in scope here. Delegates rather than re-implementing: see
     * LocalisedValueReader, the import layer's one text extraction.
     */
    private function extractTypedValue(mixed $value): string
    {
        return (new LocalisedValueReader())->read($value, '');
    }
}
