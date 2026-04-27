<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity;

use DateTimeImmutable;
use DateTimeZone;

// Transient, like AddressEntity: not tagged as import.entity, not dispatched by
// the Parser. The parent entity (TouristAttraction, …) constructs one per
// OpeningHoursSpecification node, collects the toArray() outputs and
// json_encodes the list into its own opening_hours column.
//
// Shape matches the frontend model's OpeningHour::createFromArray so the existing
// rendering pipeline keeps working without changes: validFrom/validThrough are
// stored as DateTimeImmutable so json_encode emits the legacy
// {date, timezone_type, timezone} payload the frontend expects.
class OpeningHoursEntity extends AbstractTransientEntity
{
    protected string $opens = '';
    protected string $closes = '';

    /**
     * @var list<string>
     */
    protected array $daysOfWeek = [];

    protected ?DateTimeImmutable $from = null;

    protected ?DateTimeImmutable $through = null;

    public function configure(array $node): void
    {
        $this->opens = $this->extractStringValue($node['schema:opens'] ?? null);
        $this->closes = $this->extractStringValue($node['schema:closes'] ?? null);
        $this->daysOfWeek = $this->extractDaysOfWeek($node['schema:dayOfWeek'] ?? null);
        $this->from = $this->extractDate($node['schema:validFrom'] ?? null);
        $this->through = $this->extractDate($node['schema:validThrough'] ?? null);
    }

    public function getValidThrough(): ?DateTimeImmutable
    {
        return $this->through;
    }

    public function toArray(): array
    {
        $array = [
            'opens' => $this->opens,
            'closes' => $this->closes,
        ];
        if ($this->from !== null) {
            $array['from'] = $this->from;
        }
        if ($this->through !== null) {
            $array['through'] = $this->through;
        }
        $array['daysOfWeek'] = $this->daysOfWeek;

        return $array;
    }

    private function extractDate(mixed $value): ?DateTimeImmutable
    {
        $raw = $this->extractStringValue($value);
        if ($raw === '') {
            return null;
        }

        return new DateTimeImmutable($raw, new DateTimeZone('UTC'));
    }

    /**
     * schema:dayOfWeek is either a single typed {@value} object or a list of them.
     * Each @value carries a namespaced URI (e.g. "schema:Saturday"); we drop the
     * prefix so the stored list matches what OpeningHour::getDaysOfWeek compares
     * against ("Monday", "Tuesday", …).
     *
     * @return list<string>
     */
    private function extractDaysOfWeek(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $days = [];
        foreach ($items as $item) {
            $raw = is_array($item) ? ($item['@value'] ?? '') : (string)$item;
            if ($raw === '') {
                continue;
            }
            $days[] = $this->stripNamespacePrefix((string)$raw);
        }

        return $days;
    }
}
