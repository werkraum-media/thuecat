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

use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OfferEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OpeningHoursEntity;

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
        if (is_array($value)) {
            if (!array_key_exists('@value', $value)) {
                if (array_key_exists('@value', $value[0])) {
                    $value = $value[0];
                }
            }
            return (string)$value['@value'];
        }

        return '';
    }

    /**
     * Strip the thuecat:/schema: namespace prefix from an enum @value.
     *
     * ThueCat publishes enum members as namespaced URIs (e.g. "thuecat:GothicArt").
     * The DB stores the bare member name for display/query, so we drop the prefix.
     */
    protected function stripNamespacePrefix(string $value): string
    {
        $colon = strpos($value, ':');
        return $colon === false ? $value : substr($value, $colon + 1);
    }

    /**
     * Normalise a single or multi-valued enum field into a flat list of stripped
     * member names. Accepts the three JSON-LD shapes — single typed {@value},
     * bare string, or list of either.
     *
     * @return list<string>
     */
    protected function extractEnumMembers(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $names = [];
        foreach ($items as $item) {
            $raw = is_array($item) ? ($item['@value'] ?? '') : (string)$item;
            if ($raw === '') {
                continue;
            }
            $names[] = $this->stripNamespacePrefix((string)$raw);
        }

        return $names;
    }

    protected function extractEnumList(mixed $value): string
    {
        return implode(',', $this->extractEnumMembers($value));
    }

    /**
     * Pick the @value whose @language tag matches the requested language.
     *
     * JSON-LD text fields (schema:name, schema:description, …) arrive as a flat
     * list of {@language,@value} entries, one per locale. Each language appears
     * at most once, so a match collapses the list to a single scalar for the DB.
     * A bare {@language,@value} object (no list wrapper) is accepted for the
     * common single-locale case. Lang-less entries (e.g. thuecat:Html blobs) are
     * skipped — they appear alongside the plain lang strings and are not the
     * display text.
     *
     * Returns '' when no entry matches the requested language (caller decides
     * whether to fall back).
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

        // Typed @values without @language (e.g. schema:Boolean) — fall back to
        // the plain @value so callers don't need a separate branch for booleans
        // that share a field with lang strings (see schema:petsAllowed).
        if (!isset($value['@language']) && isset($value['@value'])) {
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
     *
     * Overwrites on repeat calls with the same key — if a bucket needs to
     * merge across several JSON-LD slots (see media = image ∪ photo ∪ video),
     * pre-aggregate with collectIds() and pass the merged list in one call.
     */
    protected function recordTransient(string $key, mixed $value): void
    {
        $ids = $this->collectIds($value);
        if ($ids === []) {
            return;
        }

        $this->transients[$key] = $ids;
    }

    /**
     * Flatten a single {"@id": "…"} node, a bare id string, or a list of either
     * into a plain list of id strings. Shared building block for
     * recordTransient and for owners that need to pre-aggregate ids from
     * several JSON-LD slots into a single transient bucket.
     *
     * @return list<string>
     */
    protected function collectIds(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
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
        return $ids;
    }

    /**
     * schema:openingHoursSpecification and schema:specialOpeningHoursSpecification
     * arrive as a single OpeningHoursSpecification node or a list of them. Each
     * is self-contained, so the transient OpeningHoursEntity shapes each
     * independently; the list of arrays is then json_encoded into the owning
     * entity's target column.
     *
     * Returns '' when the field is absent so AbstractEntity::toArray's
     * array_filter drops the column rather than persisting a misleading "[]"
     * literal.
     */
    protected function buildOpeningHours(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $hours = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OpeningHoursEntity();
            $entity->configure($item);
            $hours[] = $entity->toArray();
        }

        if ($hours === []) {
            return '';
        }

        return (string)(json_encode($hours) ?: '');
    }

    /**
     * schema:makesOffer is a single Offer node or a list of them. Each carries
     * its own nested priceSpecification plus localised name/description; the
     * transient OfferEntity shapes each into the legacy Offer/Price frontend
     * shape, and the list of arrays is json_encoded into the owning entity's
     * `offers` column.
     *
     * Returns '' on absence for the same reason as buildOpeningHours.
     */
    protected function buildOffers(mixed $value, string $language): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $offers = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OfferEntity();
            $entity->configure($item, $language);
            $offers[] = $entity->toArray();
        }

        if ($offers === []) {
            return '';
        }

        return (string)(json_encode($offers) ?: '');
    }

    /**
     * Flatten thuecat:distanceToPublicTransport into "value:unit[:mean1:mean2]".
     *
     * JSON-LD shape:
     *   schema:value             -> numeric distance
     *   schema:unitCode          -> single typed @value(e.g. thuecat:MTR)
     *   thuecat:meansOfTransport -> string | {@value} | list of either
     *
     * meansOfTransport is optional; when absent the string is just "value:unit".
     * The legacy importer colon-separates every means rather than comma-joining
     * them (see Assertions fixture "350:MTR:Streetcar:CityBus").
     */
    protected function buildDistanceToPublicTransport(mixed $node): string
    {
        if (!is_array($node)) {
            return '';
        }

        $distance = $this->extractStringValue($node['schema:value'] ?? null);
        if ($distance === '') {
            return '';
        }

        $unit = $this->extractEnumList($node['schema:unitCode'] ?? null);
        $means = $this->extractEnumMembers($node['thuecat:meansOfTransport'] ?? null);

        $parts = array_merge([$distance, $unit], $means);

        return implode(':', array_filter($parts, static fn ($part) => $part !== ''));
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

    public function getPriority(): int
    {
        return $this->priority;
    }

    abstract public function handlesTypes(): array;
}
