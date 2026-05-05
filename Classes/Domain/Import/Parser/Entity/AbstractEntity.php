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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OfferEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OpeningHoursEntity;
use WerkraumMedia\ThueCat\Service\DateBasedFilter\FilterBasedOnTypo3Context;

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
     * Most buckets are plain lists of @id strings. The `media` bucket is a
     * `list<array{kind, id}>` because the resolver needs to know whether a
     * ref came from schema:photo (mainImage:true), schema:image, or
     * schema:video to shape the output correctly.
     *
     * @var array<string, list<string>|list<array{kind: string, id: string}>>
     */
    protected array $transients = [];

    /**
     * Translated scalar values for fields that carry an `@language: <code>`
     * entry in the JSON-LD. Keyed by sys_language_uid → field → translated
     * string. The parser hands the entity a `[code => sysLanguageUid]` map
     * during parse() and recordTranslation() fills this bucket.
     *
     * Reset at the start of each parse() so a reused entity instance does
     * not leak translations between nodes.
     *
     * @var array<int, array<string, string>>
     */
    protected array $translations = [];

    public function getRemoteId(array $node): string
    {
        return (string)$node['@id'];
    }

    protected function extractStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string)($value['@value'] ?? '');
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
     * Normalise a single or multi-valued JSON-LD field into a flat list of
     * stripped member names. Accepts the three JSON-LD shapes — single typed
     * {@value}, bare string, or list of either.
     *
     * Language-aware: when an item carries an `@language` tag, only entries
     * matching $language are kept. Items without `@language` (the typical
     * shape for thuecat: namespaced URI values) are accepted regardless so
     * the same set lands in every per-language pass. Callers that want the
     * default-language pass should pass the site's default language code.
     *
     * @return list<string>
     */
    protected function extractConcatenatedMembers(mixed $value, string $language): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $names = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                if (isset($item['@language']) && $item['@language'] !== $language) {
                    continue;
                }
                $raw = (string)($item['@value'] ?? '');
            } else {
                $raw = (string)$item;
            }
            if ($raw === '') {
                continue;
            }
            $names[] = $this->stripNamespacePrefix($raw);
        }

        return $names;
    }

    /**
     * Comma-joined variant of extractConcatenatedMembers — the canonical
     * shape stored in scalar columns that hold multi-valued URI lists
     * (slogan, sanitation, paymentAccepted, …). Treated identically to a
     * localised string: callers extract once per language and either feed
     * the default-language value into the row or recordTranslation() it
     * onto a per-language entry.
     */
    protected function extractConcatenatedString(mixed $value, string $language): string
    {
        return implode(',', $this->extractConcatenatedMembers($value, $language));
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
     * Overwrites on repeat calls with the same key. The media bucket carries
     * per-slot kind info so it uses recordMediaTransient() instead.
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
     * Record a single translated field value into the entity's translations
     * bucket, keyed by sys_language_uid → field name.
     *
     * Empty strings are dropped — they mean the JSON-LD has no `@language`
     * entry for the requested locale, so there is no translation to store.
     * That keeps the bucket presence-based: only fields that actually carry
     * a translation appear, mirroring how toArray's array_filter drops blank
     * default-language columns.
     */
    protected function recordTranslation(string $fieldName, string $value, int $sysLanguageUid): void
    {
        if ($value === '') {
            return;
        }
        $this->translations[$sysLanguageUid][$fieldName] = $value;
    }

    /**
     * Build the `media` bucket by pairing schema:photo / schema:image /
     * schema:video refs with a `kind` tag the resolver uses to set
     * mainImage + type on the shaped JSON output. Emits photo refs first,
     * then image, then video — mirrors the legacy ordering where the
     * schema:photo ref becomes the mainImage entry at index 0.
     *
     * Duplicates across slots are kept: the same dms_* may legitimately
     * appear as both schema:photo AND schema:image on the source, and
     * the legacy output preserved both entries.
     */
    protected function recordMediaTransient(mixed $photo, mixed $image, mixed $video): void
    {
        $entries = [];
        foreach ($this->collectIds($photo) as $id) {
            $entries[] = ['kind' => 'photo', 'id' => $id];
        }
        foreach ($this->collectIds($image) as $id) {
            $entries[] = ['kind' => 'image', 'id' => $id];
        }
        foreach ($this->collectIds($video) as $id) {
            $entries[] = ['kind' => 'video', 'id' => $id];
        }

        if ($entries === []) {
            return;
        }

        $this->transients['media'] = $entries;
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
        $entities = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OpeningHoursEntity();
            $entity->configure($item);
            $entities[] = $entity;
        }

        // Drop entries whose validThrough is before the reference date so stale
        // seasonal hours don't pile up in the DB. Reference date is the TYPO3
        // Context's date aspect (now in production, fixed in tests). The filter
        // itself is a tiny stateless wrapper, so newing it here keeps the
        // entity free of DI plumbing — entities are constructed bare in unit
        // tests and via the import.entity ServiceLocator in production, neither
        // of which permits constructor DI. Context is a TYPO3 core singleton,
        // so it must be fetched via GeneralUtility to honour any aspect set by
        // the caller (e.g. tests pinning "now" via setDateAspect()).
        /** @var list<OpeningHoursEntity> $filtered */
        $filtered = (new FilterBasedOnTypo3Context(GeneralUtility::makeInstance(Context::class)))
            ->filterOutPreviousDates(
                $entities,
                static fn (OpeningHoursEntity $hour) => $hour->getValidThrough()
            )
        ;

        if ($filtered === []) {
            return '';
        }

        $hours = [];
        foreach ($filtered as $entity) {
            $hours[] = $entity->toArray();
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
    protected function buildDistanceToPublicTransport(mixed $node, string $language): string
    {
        if (!is_array($node)) {
            return '';
        }

        $distance = $this->extractStringValue($node['schema:value'] ?? null);
        if ($distance === '') {
            return '';
        }

        $unit = $this->extractConcatenatedString($node['schema:unitCode'] ?? null, $language);
        $means = $this->extractConcatenatedMembers($node['thuecat:meansOfTransport'] ?? null, $language);

        $parts = array_merge([$distance, $unit], $means);

        return implode(':', array_filter($parts, static fn ($part) => $part !== ''));
    }

    public function getTransients(): array
    {
        return $this->transients;
    }

    /** @return array<int, array<string, string>> */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /** @return array<string, string|int|float> */
    public function toArray(): array
    {
        $array = get_object_vars($this);
        // table / transients / priority / translations are framework
        // metadata, not DB columns.
        unset(
            $array['table'],
            $array['transients'],
            $array['priority'],
            $array['translations'],
        );

        /** @var array<string, string|int|float> $filtered */
        $filtered = array_filter($array);
        return $filtered;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    abstract public function handlesTypes(): array;
}
