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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

abstract class AbstractEntity implements EntityInterface
{
    protected int $priority = 10;

    /**
     * Per-record side channel for unresolved references (e.g. schema:containedInPlace).
     *
     * The parser cannot decide the target table for a bare {"@id": "…"} stub — the
     * JSON-LD node only holds the id, not the type. The resolver runs post-parse,
     * looks each id up via API + DB cache, and writes the real relation field on
     * this record. Keeping the bucket on the entity guarantees it stays bound to
     * its owning (table, remote_id) pair even when an import contains many records
     * that share similar raw refs.
     *
     * Keys match the JSON-LD field name with the schema:/thuecat: prefix stripped.
     *
     * @var array<string, list<string>>
     */
    protected array $transients = [];

    public function getRemoteId(array $node): string
    {
        return (string)$node['@id'];
    }

    protected function prefixRelation(string $remoteId): string
    {
        return 'REF:' . $remoteId;
    }


    protected function extractStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string)($value['@value'] ?? '');
        }

        return '';
    }

    protected function extractLanguageValue(mixed $value): string
    {
        if (is_array($value) && isset($value['@value'])) {
            return (string)$value['@value'];
        }

        return '';
    }

    /**
     * Record a raw JSON-LD relation value for the resolver.
     *
     * Accepts the three shapes JSON-LD emits — a single {"@id": "…"} object, a
     * bare string id, or a list of either — and stores a plain list of @id
     * strings. A null / empty / all-blank input is ignored so the transient map
     * stays truthy-only and easy for the resolver to iterate.
     *
     * $key must be the JSON-LD field name with the schema:/thuecat: prefix
     * stripped (e.g. 'containedInPlace', not 'schema:containedInPlace').
     */
    protected function recordTransient(string $key, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $ids = [];
        foreach ($items as $item) {
            $id = is_array($item) ? ($item['@id'] ?? '') : (string)$item;
            if ($id === '') {
                continue;
            }
            $ids[] = (string)$id;
        }

        if ($ids === []) {
            return;
        }

        $this->transients[$key] = $ids;
    }

    public function getTransients(): array
    {
        return $this->transients;
    }

    public function toArray(): array
    {
        $array = get_object_vars($this);
        unset($array['table'], $array['transients']);

        return array_filter($array);
    }

    public function getPriority():int
    {
        return $this->priority;
    }

    abstract public function handlesTypes(): array;
}