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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

use WerkraumMedia\ThueCat\Import\Parser\Entity\KeywordEntry;

/** Reads `schema:keywords` into shape-tagged entries. */
class KeywordReader
{
    /**
     * @param array<string, mixed> $node
     *
     * @return list<KeywordEntry>
     */
    public function read(array $node): array
    {
        $value = $node['schema:keywords'] ?? null;
        if (!is_array($value)) {
            return [];
        }

        $items = array_is_list($value) ? $value : [$value];

        $entries = [];
        $seen = [];
        foreach ($items as $item) {
            $entry = $this->toEntry($item);
            if ($entry === null) {
                continue;
            }
            // Same term referenced twice would otherwise cost a duplicate fetch.
            $key = $entry->shape . '|' . $entry->value;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $entries[] = $entry;
        }

        return $entries;
    }

    private function toEntry(mixed $item): ?KeywordEntry
    {
        if (!is_array($item)) {
            return null;
        }

        $reference = $item['@id'] ?? null;
        if (is_string($reference) && $reference !== '') {
            return new KeywordEntry(KeywordEntry::SHAPE_REFERENCE, $reference);
        }

        $literal = $item['@value'] ?? null;
        if (!is_string($literal) || $literal === '') {
            return null;
        }

        $usageType = $item['@type'] ?? null;
        if (is_array($usageType)) {
            $usageType = $usageType[0] ?? null;
        }
        // A datatype such as xsd:string marks a plain literal, not a vocabulary.
        if (is_string($usageType) && $usageType !== '' && !str_starts_with($usageType, 'xsd:')) {
            return new KeywordEntry(KeywordEntry::SHAPE_ONTOLOGY, $literal, $usageType);
        }

        return new KeywordEntry(KeywordEntry::SHAPE_FREE_TEXT, $literal);
    }
}
