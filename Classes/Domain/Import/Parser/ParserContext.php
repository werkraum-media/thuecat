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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser;

/**
 * Narrow collaborator passed to entities during configure().
 *
 * Deliberately does NOT expose the DataHandlerPayload: collection stays the
 * Parser's responsibility. Entities only resolve relation values to REF: strings
 * or trigger recursion into child nodes — everything else is out of scope.
 */
class ParserContext
{
    /**
     * The site-language tag that picks which localised @value wins for the default
     * row (e.g. "de"). Source fixtures always carry exactly one entry per language
     * per field, so matching on @language collapses a multi-locale list back to a
     * single scalar. Supplied by the ImporterCommand from the target folder's site
     * configuration; defaults to German for tests and existing callers.
     */
    public function __construct(
        private readonly Parser $parser,
        public readonly string $language = 'de',
    ) {
    }

    /**
     * Normalise an incoming relation value to comma-joined REF:<remote_id> strings.
     *
     * JSON-LD relations come in three shapes — single object {"@id": "…"}, a bare
     * string id, or a list of either. DataHandler later expects a flat scalar field,
     * so we flatten here and let the post-processor swap REF:<id> for real uids.
     */
    public function reference(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $refs = [];
        foreach ($items as $item) {
            $id = is_array($item) ? ($item['@id'] ?? '') : (string)$item;
            if ($id === '') {
                continue;
            }
            $refs[] = 'REF:' . $id;
        }

        return implode(',', $refs);
    }

    /**
     * Recurse into a child node. Returns REF:<remote_id> for persisted entities,
     * '' when no registered entity handles the node's @type(e.g. blank intangibles).
     */
    public function parseNode(array $node): string
    {
        return $this->parser->parseNode($node);
    }
}
