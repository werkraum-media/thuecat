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

namespace WerkraumMedia\ThueCat\Frontend\Cache;

use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;

/**
 * Entry identifiers for the teaser, list and search-mask caches.
 */
class CacheIdentifierFactory
{
    public function forList(
        int $pluginUid,
        TouristAttractionDemand $demand,
        int $currentPage,
        int $languageId
    ): string {
        return $this->build('l', [
            (string)$pluginUid,
            $this->hashDemand($demand),
            (string)$currentPage,
            (string)$languageId,
        ]);
    }

    /**
     * Keyed on the page rather than the sibling list it reflects: resolving
     * that sibling is the work a cache hit skips. Without the pagination page,
     * since the mask is identical across the pages of one demand.
     */
    public function forSearchMask(
        int $pluginUid,
        int $pageUid,
        TouristAttractionDemand $demand,
        int $languageId
    ): string {
        return $this->build('m', [
            (string)$pluginUid,
            (string)$pageUid,
            $this->hashDemand($demand),
            (string)$languageId,
        ]);
    }

    /**
     * Names no plugin, demand or page, so every list showing this record shares
     * the entry. The table is part of the identity because uids are unique only
     * within a table.
     */
    public function forTeaser(string $table, int $recordUid, int $detailPageUid, int $languageId): string
    {
        return $this->build('t', [
            $table,
            (string)$recordUid,
            (string)$detailPageUid,
            (string)$languageId,
        ]);
    }

    /**
     * @param list<string> $parts
     */
    private function build(string $prefix, array $parts): string
    {
        return $prefix . '_' . implode('_', $parts);
    }

    /**
     * One token, equal for equal filter states.
     *
     * Derived from whatever the demand carries: naming known filters here would
     * keep working while silently no longer telling two filter states apart as
     * more become editor-selectable.
     */
    private function hashDemand(TouristAttractionDemand $demand): string
    {
        /** @var array<string, string|list<string>> $canonical */
        $canonical = [];
        foreach ($demand->getQueryParameters() as $name => $value) {
            $canonical[$name] = $this->canonicaliseValue($value);
        }
        ksort($canonical);

        return hash('xxh128', json_encode($canonical, JSON_THROW_ON_ERROR));
    }

    /**
     * Scalars become strings so 1 and '1' agree; lists are deduplicated and
     * sorted so a URL's value order does not matter.
     *
     * @return string|list<string>
     */
    private function canonicaliseValue(mixed $value): string|array
    {
        if (!is_array($value)) {
            return $this->stringify($value);
        }

        $values = array_map($this->stringify(...), $value);
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    /** Non-scalars are encoded rather than cast, which would fatal. */
    private function stringify(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
