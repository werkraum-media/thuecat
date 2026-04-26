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
    private array $dataMap = [];

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

    /**
     * Staged DataHandler cmdmap entries. Outer key is the table; second key
     * is the target uid (as string, since cmdmap targets are existing rows);
     * inner is a list of `[$command, $value]` tuples. The Importer fans these
     * out into the `$cmd[$table][$uid][$command] = $value` shape that
     * DataHandler::start() consumes.
     *
     * Today only `localize` (value = sys_language_uid) lands here, but the
     * shape is generic so future commands (copy, move, delete, …) can land
     * without renaming the API.
     *
     * @var array<string, array<int|string, list<array{0: string, 1: int|string}>>>
     */
    private array $cmdMap = [];

    /**
     * Outer keys of rows that came in via addEntity — i.e. default-language
     * rows the parser produced. Translation rows added via addTranslationRow
     * are deliberately excluded so the Importer can hand the logger a
     * default-language-only snapshot, keeping the user-facing import counts
     * matching what people see in the parser output.
     *
     * @var array<string, array<int|string, true>>
     */
    private array $defaultLanguageKeys = [];

    public function addEntity(EntityInterface $entity): void
    {
        /** @var string $table */
        $table = $entity->table;
        $row = $entity->toArray();
        $remoteId = (string)$row['remote_id'];

        if (!isset($this->dataMap[$table])) {
            $this->dataMap[$table] = [];
        }

        $this->dataMap[$table][$remoteId] = $row;
        $this->defaultLanguageKeys[$table][$remoteId] = true;

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
     * Drop a row, its transients, and its translations from the payload. Used
     * by the resolver when an entity's remote_id has already been staged in
     * an earlier pass of this importer run (status = updated): re-staging
     * would either duplicate the dataMap entry or schedule a redundant
     * DataHandler update for a row already on its way in. The
     * defaultLanguageKeys entry is dropped too so the logger snapshot only
     * counts rows that actually go through DataHandler this run.
     */
    public function dropRow(string $table, string $key): void
    {
        unset(
            $this->dataMap[$table][$key],
            $this->transients[$table][$key],
            $this->translations[$table][$key],
            $this->defaultLanguageKeys[$table][$key]
        );

        if (($this->dataMap[$table] ?? []) === []) {
            unset($this->dataMap[$table]);
        }
        if (($this->transients[$table] ?? []) === []) {
            unset($this->transients[$table]);
        }
        if (($this->translations[$table] ?? []) === []) {
            unset($this->translations[$table]);
        }
        if (($this->defaultLanguageKeys[$table] ?? []) === []) {
            unset($this->defaultLanguageKeys[$table]);
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
        if (!isset($this->dataMap[$table][$oldKey])) {
            return;
        }

        $this->dataMap[$table][$newKey] = $this->dataMap[$table][$oldKey];
        unset($this->dataMap[$table][$oldKey]);

        if (isset($this->defaultLanguageKeys[$table][$oldKey])) {
            unset($this->defaultLanguageKeys[$table][$oldKey]);
            $this->defaultLanguageKeys[$table][$newKey] = true;
        }
    }

    /**
     * Write a single field onto a row already present in the payload.
     * Used by the resolver to inject `pid` and, later, to fill resolved
     * uid references into relation fields.
     */
    public function setField(string $table, string $key, string $field, string|int|float $value): void
    {
        if (!isset($this->dataMap[$table][$key])) {
            return;
        }

        $this->dataMap[$table][$key][$field] = $value;
    }

    /**
     * Append a resolved uid (or NEW… placeholder) to a comma-separated
     * relation field. Dedupes so repeated resolver passes stay idempotent.
     */
    public function setRelationField(string $table, string $key, string $field, string|int $value): void
    {
        if (!isset($this->dataMap[$table][$key])) {
            return;
        }

        $existing = (string)($this->dataMap[$table][$key][$field] ?? '');
        $values = $existing === '' ? [] : explode(',', $existing);
        $value = (string)$value;

        if (!in_array($value, $values, true)) {
            $values[] = $value;
        }

        $this->dataMap[$table][$key][$field] = implode(',', $values);
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
        $this->dataMap[$table][$key] = $fields;
    }

    /**
     * Stage a DataHandler cmdmap entry on an existing target uid. Dedupes so
     * repeated resolver passes (scenario 2 → second pass) don't queue the
     * same command twice.
     */
    public function addCmdMap(string $table, string $key, string $command, int|string $value): void
    {
        $entry = [$command, $value];
        $existing = $this->cmdMap[$table][$key] ?? [];
        foreach ($existing as $candidate) {
            if ($candidate === $entry) {
                return;
            }
        }
        $existing[] = $entry;
        $this->cmdMap[$table][$key] = $existing;
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
        foreach ($other->dataMap as $table => $rows) {
            foreach ($rows as $remoteId => $row) {
                if (isset($this->dataMap[$table][$remoteId])) {
                    continue;
                }
                $this->dataMap[$table][$remoteId] = $row;
                if (isset($other->defaultLanguageKeys[$table][$remoteId])) {
                    $this->defaultLanguageKeys[$table][$remoteId] = true;
                }
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

        foreach ($other->cmdMap as $table => $entriesByKey) {
            foreach ($entriesByKey as $key => $entries) {
                foreach ($entries as $entry) {
                    $this->addCmdMap($table, (string)$key, $entry[0], $entry[1]);
                }
            }
        }
    }

    /**
     * @return array<string, array<int|string, array<string, string|int|float>>>
     */
    public function getDataMap(): array
    {
        return $this->dataMap;
    }

    /**
     * Same shape as getDataMap, restricted to rows the parser produced via
     * addEntity (default-language rows). Translation rows added during the
     * resolver's drain are excluded.
     *
     * @return array<string, array<int|string, array<string, string|int|float>>>
     */
    public function getDefaultLanguageDataMap(): array
    {
        $result = [];
        foreach ($this->defaultLanguageKeys as $table => $keys) {
            foreach ($keys as $key => $_) {
                if (!isset($this->dataMap[$table][$key])) {
                    continue;
                }
                $result[$table][$key] = $this->dataMap[$table][$key];
            }
        }
        return $result;
    }

    /**
     * Drop the staged datamap. Used by the Importer between loop passes
     * after process_datamap() has consumed it. Default-language key tracking
     * stays so a later pass that re-stages translation rows still keeps the
     * "what counts as default language" view accurate.
     */
    public function clearDataMap(): void
    {
        $this->dataMap = [];
    }

    /**
     * Drop the staged cmdmap. Counterpart to clearDataMap, used after
     * process_cmdmap() has consumed it.
     */
    public function clearCmdMap(): void
    {
        $this->cmdMap = [];
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

    /**
     * @return array<string, array<int|string, list<array{0: string, 1: int|string}>>>
     */
    public function getCmdMap(): array
    {
        return $this->cmdMap;
    }
}
