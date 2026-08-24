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

/**
 * The single text-extraction helper for the import layer.
 *
 * Reads the first `@value` matching the requested language out of any shape the
 * source emits: a list of language-tagged entries, a single such object, an
 * untagged typed value, or a bare scalar. A collaborator rather than a
 * base-class method because the two entity roots are deliberately unrelated.
 *
 * An untagged entry is the fallback, not an edge case: typed enums carry no
 * language tag and must read in every language pass.
 */
class LocalisedValueReader
{
    public function read(mixed $value, string $language): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value) || $value === []) {
            return '';
        }

        if (array_is_list($value)) {
            return $this->readFromList($value, $language);
        }

        return $this->readNode($value, $language);
    }

    /**
     * Language match wins over an untagged entry wherever it appears in the
     * list, so a fallback never shadows a real translation.
     *
     * @param list<mixed> $items
     */
    private function readFromList(array $items, string $language): string
    {
        $fallback = '';

        foreach ($items as $item) {
            if (is_string($item)) {
                return $item;
            }
            if (!is_array($item)) {
                continue;
            }

            if (($item['@language'] ?? null) === $language) {
                return $this->stringifyValue($item['@value'] ?? null);
            }

            if ($fallback === '' && !isset($item['@language'])) {
                $fallback = $this->stringifyValue($item['@value'] ?? null);
            }
        }

        return $fallback;
    }

    /**
     * @param array<mixed> $node
     */
    private function readNode(array $node, string $language): string
    {
        $tag = $node['@language'] ?? null;
        if ($tag !== null && $tag !== $language) {
            return '';
        }

        return $this->stringifyValue($node['@value'] ?? null);
    }

    /**
     * A non-scalar @value is malformed; '' rather than PHP's "Array".
     */
    private function stringifyValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return (string)$value;
    }
}
