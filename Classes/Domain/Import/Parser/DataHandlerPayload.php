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

use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\EntityInterface;

class DataHandlerPayload
{
    /** @var array<string, array<int|string, array<string, string|int|float>>> */
    private array $data = [];

    /**
     * Unresolved references, bound to (table, remote_id) so the resolver can
     * write each resolved uid back into the correct row without risk of mixing
     * up records that share similar raw refs.
     *
     * Most buckets carry a `list<string>` of @id URLs that the resolver looks
     * up in the DB or fetches via API (managedBy, containedInPlace, …). The
     * `media` bucket is the exception: its entries are `{kind, id}` tuples
     * because the resolver needs the `schema:photo`/`schema:image`/
     * `schema:video` origin of each ref to set `mainImage` and `type` on the
     * shaped JSON output — a flat list of ids would lose that information.
     *
     * @var array<string, array<string, array<string, list<string>|list<array{kind: string, id: string}>>>>
     */
    private array $transients = [];

    /**
     * Translated scalar values, bound to the same (table, remote_id) shape
     * as transients. Inner key is the target sys_language_uid; inner value
     * is a partial row holding only the fields whose JSON-LD source carries
     * a matching `@language` entry. The DataHandler will use each entry as
     * the data part of an l18n_parent / l10n_source insert or update for
     * that record.
     *
     * @var array<string, array<string, array<int, array<string, string>>>>
     */
    private array $translations = [];

    public function addEntity(EntityInterface $entity): void
    {
        /** @var string $table */
        $table = $entity->table;
        $row = $entity->toArray();
        $remoteId = (string)$row['remote_id'];

        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }

        $this->data[$table][$remoteId] = $row;

        $entityTransients = $entity->getTransients();
        if ($entityTransients !== []) {
            $this->transients[$table][$remoteId] = $entityTransients;
        }

        $entityTranslations = $entity->getTranslations();
        if ($entityTranslations !== []) {
            $this->translations[$table][$remoteId] = $entityTranslations;
        }
    }

    /**
     * Swap the outer key of an already-registered row. Used by the resolver
     * to replace the remote-id URL with either an existing uid or a
     * StringUtility::getUniqueId('NEW') placeholder, turning the payload
     * into a valid DataHandler datamap in place.
     */
    public function rekeyRow(string $table, string $oldKey, string $newKey): void
    {
        if (!isset($this->data[$table][$oldKey])) {
            return;
        }

        $this->data[$table][$newKey] = $this->data[$table][$oldKey];
        unset($this->data[$table][$oldKey]);
    }

    /**
     * Write a single field onto a row already present in the payload.
     * Used by the resolver to inject `pid` and, later, to fill resolved
     * uid references into relation fields.
     */
    public function setField(string $table, string $key, string $field, string|int|float $value): void
    {
        if (!isset($this->data[$table][$key])) {
            return;
        }

        $this->data[$table][$key][$field] = $value;
    }

    /**
     * Append a resolved uid (or NEW… placeholder) to a comma-separated
     * relation field. Dedupes so repeated resolver passes stay idempotent.
     */
    public function setRelationField(string $table, string $key, string $field, string|int $value): void
    {
        if (!isset($this->data[$table][$key])) {
            return;
        }

        $existing = (string)($this->data[$table][$key][$field] ?? '');
        $values = $existing === '' ? [] : explode(',', $existing);
        $value = (string)$value;

        if (!in_array($value, $values, true)) {
            $values[] = $value;
        }

        $this->data[$table][$key][$field] = implode(',', $values);
    }

    /**
     * Register a translation row in the data array under its own outer key
     * (the resolved translation uid as string, or a NEW… placeholder once
     * scenarios 2/3 land). DataHandler then treats the row as an update or
     * insert in the same datamap pass as the parent. Bookkeeping fields
     * (sys_language_uid, l10n_parent, l10n_source) are intentionally not
     * written here: when the translation row already exists the DataHandler
     * leaves them alone, and for the create-then-update path scenarios 2/3
     * will set them where appropriate.
     *
     * @param array<string, string|int|float> $fields
     */
    public function addTranslationRow(string $table, string $key, array $fields): void
    {
        $this->data[$table][$key] = $fields;
    }

    /**
     * Drop one language entry from the translations bucket and clean up
     * empty parents the same way removeTransient does.
     */
    public function removeTranslation(string $table, string $remoteId, int $sysLanguageUid): void
    {
        if (!isset($this->translations[$table][$remoteId][$sysLanguageUid])) {
            return;
        }

        unset($this->translations[$table][$remoteId][$sysLanguageUid]);

        if (($this->translations[$table][$remoteId] ?? []) === []) {
            unset($this->translations[$table][$remoteId]);
        }
        if (($this->translations[$table] ?? []) === []) {
            unset($this->translations[$table]);
        }
    }

    /**
     * Drop a single @id from a transient bucket. Empty buckets and empty
     * row/table entries are cleaned up so `getTransients() === []` means
     * the resolver is done.
     *
     * Handles both bucket shapes: simple `list<string>` (ref→uid buckets)
     * and `list<array{kind, id}>` (the media bucket) — the id field is
     * compared in the tuple case.
     */
    public function removeTransient(string $table, string $remoteId, string $bucket, string $reference): void
    {
        if (!isset($this->transients[$table][$remoteId][$bucket])) {
            return;
        }

        $filtered = array_values(array_filter(
            $this->transients[$table][$remoteId][$bucket],
            static function (string|array $entry) use ($reference): bool {
                if (is_array($entry)) {
                    return $entry['id'] !== $reference;
                }
                return $entry !== $reference;
            }
        ));

        if ($filtered === []) {
            unset($this->transients[$table][$remoteId][$bucket]);
        } else {
            $this->transients[$table][$remoteId][$bucket] = $filtered;
        }

        if (($this->transients[$table][$remoteId] ?? []) === []) {
            unset($this->transients[$table][$remoteId]);
        }
        if (($this->transients[$table] ?? []) === []) {
            unset($this->transients[$table]);
        }
    }

    /**
     * Append rows and transients from another payload. Existing rows (keyed by
     * remote_id at this stage) are left untouched — same @id fetched twice
     * must not clobber whatever is already there. Used by the resolver when a
     * transient reference triggers a follow-up fetch that yields more rows.
     */
    public function mergeFrom(self $other): void
    {
        foreach ($other->data as $table => $rows) {
            foreach ($rows as $remoteId => $row) {
                if (isset($this->data[$table][$remoteId])) {
                    continue;
                }
                $this->data[$table][$remoteId] = $row;
            }
        }

        foreach ($other->transients as $table => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $remoteId => $buckets) {
                if (isset($this->transients[$table][$remoteId])) {
                    continue;
                }
                $this->transients[$table][$remoteId] = $buckets;
            }
        }

        foreach ($other->translations as $table => $rowsByRemoteId) {
            foreach ($rowsByRemoteId as $remoteId => $perLanguage) {
                if (isset($this->translations[$table][$remoteId])) {
                    continue;
                }
                $this->translations[$table][$remoteId] = $perLanguage;
            }
        }
    }

    /**
     * @return array<string, array<int|string, array<string, string|int|float>>>
     */
    public function getPayload(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, array<string, array<string, list<string>|list<array{kind: string, id: string}>>>>
     */
    public function getTransients(): array
    {
        return $this->transients;
    }

    /**
     * @return array<string, array<string, array<int, array<string, string>>>>
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
