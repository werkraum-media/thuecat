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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Fake;

use ReflectionProperty;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

/**
 * Test double that stands in where a real ParserContext is required.
 *
 * Extends the real type so it's substitutable, but deliberately skips the parent
 * constructor: a unit-tested entity has no business wiring a live Parser +
 * ServiceLocator. parseNode() records every call for assertions and returns a
 * predictable REF:<@id>, so callers can verify that a relation triggered a
 * recursion — and with which node.
 */
final class ParserContextFake extends ParserContext
{
    /** @var list<array> Nodes passed to parseNode(), in call order. */
    public array $parsedNodes = [];

    public function __construct(string $language = 'de')
    {
        // No parent::__construct() — the readonly Parser property stays unset
        // on purpose; nothing here touches it. We assign the public $language
        // by reflection so tests can pick a non-German default when needed.
        $ref = new ReflectionProperty(ParserContext::class, 'language');
        $ref->setValue($this, $language);
    }

    public function reference(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $refs = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $id = is_string($item['@id'] ?? null) ? $item['@id'] : '';
            } elseif (is_scalar($item)) {
                $id = (string)$item;
            } else {
                $id = '';
            }
            if ($id === '') {
                continue;
            }
            $refs[] = 'REF:' . $id;
        }

        return implode(',', $refs);
    }

    public function parseNode(array $node): string
    {
        $this->parsedNodes[] = $node;
        $id = $node['@id'] ?? null;
        $id = is_string($id) ? $id : '';
        return $id === '' ? '' : 'REF:' . $id;
    }
}
