<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

// Inline 1:n child of a Place entity: one
// row per schema:OpeningHoursSpecification node in tx_thuecat_opening_hours.
//
// Inline IRRE FK wiring: configure() stages the parent's remote_id under a
// per-parent-table bucket. The Resolver resolves the parent key and appends this
// child to the parent's inline field. The
// bucket is per table so the Resolver picks the right parent table + inline field.
//
// remote_id pattern: <parentRemoteId>::oh::<type>::<day>::<opens>. Deterministic
// so re-imports upsert the same row instead of accumulating duplicates.
class OpeningHourSpecificationEntity extends AbstractEntity
{
    public const TYPE_REGULAR = 'regular';
    public const TYPE_SPECIAL = 'special';

    public string $table = 'tx_thuecat_opening_hours';

    protected string $remote_id = '';
    protected string $specification_type = self::TYPE_REGULAR;
    protected string $day_of_week = '';
    protected string $opens = '';
    protected string $closes = '';
    protected ?string $valid_from = null;
    protected ?string $valid_through = null;

    /**
     * Bypasses the JSON-LD parse() path. The parent entity already extracted the
     * spec node and resolved a single weekday (a spec may list several days; the
     * builder emits one child per day).
     *
     * @param array<string, mixed> $node a single OpeningHoursSpecification node
     */
    public function configure(string $parentRemoteId, string $specificationType, string $dayOfWeek, array $node): void
    {
        $this->specification_type = $specificationType;
        $this->day_of_week = $dayOfWeek;
        $this->opens = $this->extractStringValue($node['schema:opens'] ?? null);
        $this->closes = $this->extractStringValue($node['schema:closes'] ?? null);
        $this->valid_from = $this->extractDate($node['schema:validFrom'] ?? null);
        $this->valid_through = $this->extractDate($node['schema:validThrough'] ?? null);

        // Parent remote_id is the prefix (split on `::oh::`); the Resolver reads
        // it back to wire this child to its parent's inline field. Include the
        // validity window: two specs can share day+opens and differ only by
        // validFrom/validThrough (same Saturday hours for two seasons), so those
        // must be part of the key to stay distinct on upsert.
        $this->remote_id = implode('::', [
            $parentRemoteId,
            'oh',
            $specificationType,
            $this->day_of_week,
            $this->opens,
            (string)$this->valid_from,
            (string)$this->valid_through,
        ]);
    }

    /**
     * No-op: manufactured by the parent, never dispatched from a JSON-LD node.
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        // Intentionally empty — see class docblock.
    }

    /**
     * Empty so the Parser's @type → entity dispatch never picks this up.
     */
    public function handlesTypes(): array
    {
        return [];
    }

    /**
     * schema:Date @value is "YYYY-MM-DD"; the date dbType column stores it as-is.
     */
    private function extractDate(mixed $value): ?string
    {
        $raw = $this->extractStringValue($value);
        return $raw === '' ? null : $raw;
    }
}
