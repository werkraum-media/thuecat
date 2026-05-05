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

// Shared base for nested JSON-LD shapes whose rendered form is a JSON blob on
// a parent entity's column (Address, OpeningHours, …). Transients are not
// registered as `import.entity` services and not dispatched by the Parser —
// the parent owns construction, configuration, and json_encoding.
//
// Kept deliberately separate from Entity\AbstractEntity: top-level entities
// carry transients, priorities, handlesTypes(), and the DataHandler payload
// contract; transients have none of that. Only the shared value-extraction
// helpers live here.
abstract class AbstractTransientEntity
{
    abstract public function toArray(): array;

    /**
     * Read a single {@type, @value} node's @value. Returns '' for non-arrays
     * so the helper is safe to feed raw JSON-LD slots that may be missing.
     */
    protected function extractStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string)($value['@value'] ?? '');
        }

        return '';
    }

    /**
     * Read a single {@language, @value} node's @value. Accepts any array that
     * carries a @value key (so it also handles typed non-language values), and
     * returns '' otherwise. Kept separate from extractStringValue to document
     * intent at the call site.
     */
    protected function extractLanguageValue(mixed $value): string
    {
        if (is_array($value) && isset($value['@value'])) {
            return (string)$value['@value'];
        }

        return '';
    }

    /**
     * Drop the `thuecat:` / `schema:` prefix from an enum URI so the stored
     * value matches the bare member name used by the frontend models.
     */
    protected function stripNamespacePrefix(string $value): string
    {
        $colon = strpos($value, ':');
        return $colon === false ? $value : substr($value, $colon + 1);
    }

    /**
     * Pick the @value that matches $language from a localised slot. Mirrors
     * AbstractEntity::extractLocalisedValue intentionally — the two class
     * hierarchies deliberately don't share a base (see class docblock), so
     * we keep a dedicated copy here rather than leaking transient helpers
     * into AbstractEntity.
     *
     * Returns '' when no matching translation exists, so the JSON blob for a
     * non-default language row carries empty strings rather than a German
     * fallback (mirrors the legacy Offer/Price frontend shape).
     */
    protected function extractLocalisedValue(mixed $value, string $language): string
    {
        if (!is_array($value)) {
            return '';
        }

        if (array_is_list($value)) {
            foreach ($value as $item) {
                if (is_array($item) && ($item['@language'] ?? null) === $language && isset($item['@value'])) {
                    return (string)$item['@value'];
                }
            }
            return '';
        }

        if (($value['@language'] ?? null) === $language && isset($value['@value'])) {
            return (string)$value['@value'];
        }

        return '';
    }
}
