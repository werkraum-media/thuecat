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

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;

final class ResolverContext
{
    /**
     * Default-language status: uid is known for the remote_id (either looked
     * up from the DB or assigned as a NEW… placeholder), but the row has not
     * yet been staged into the dataMap this run. A relation field MUST NOT
     * pull from a 'found' uid unless the parent is mid-walk and will commit
     * before returning — otherwise we'd write a uid into a relation while
     * the target row is still stale in the DB and unrefreshed.
     */
    public const STATUS_FOUND = 'found';

    /**
     * Default-language status: row staged into the dataMap this run. Once a
     * remote_id is 'updated', it is done for the rest of the importer run —
     * never re-fetched, never re-staged, never revisited. Subsequent
     * sightings collapse to "write the cached uid into the parent's relation
     * field and return."
     */
    public const STATUS_UPDATED = 'updated';

    /**
     * Translation status: cmdMap localize staged this run; the localized row
     * exists (or will, once DataHandler processes the cmdmap). The
     * translated fields still need to be written via dataMap in a subsequent
     * pass before the (remote_id, sys_language_uid) pair is done.
     */
    public const TRANSLATION_CREATED = 'created';

    /**
     * Translation status: dataMap override of the translated fields staged
     * this run. The (remote_id, sys_language_uid) pair is done — never
     * revisit, never re-localize, never re-stage.
     */
    public const TRANSLATION_UPDATED = 'updated';

    /**
     * remote_id → current outer key for the in-flight import.
     *
     * Lifetime spans every Resolver round for a single
     * Importer::importConfiguration call. Values evolve:
     *   - first sighting of a new remote_id: NEW… placeholder
     *   - after DataHandler runs: promoted to the assigned uid (string)
     *   - pre-existing rows: the uid string from the first sighting
     * Kept on the context (not on the Resolver, which is a DI singleton)
     * so concurrent imports cannot cross-contaminate each other.
     *
     * @var array<string, string>
     */
    public array $remoteIdToKey = [];

    /**
     * remote_id → table the entity was staged or found in. Populated whenever
     * a row enters the dataMap or a DB lookup hits. The upstream graph mixes
     * types under shared bucket names (e.g. containedInPlace pointing at a
     * region instead of a town); when wiring relations we use this map to
     * silently skip cross-table references — the entity itself still gets
     * imported under its real table, just without that particular relation.
     *
     * @var array<string, string>
     */
    public array $remoteIdToTable = [];

    /**
     * Category remote_id → staged key (existing uid or NEW placeholder). Lives on
     * the context because resolve() runs once per root but a category recurs
     * across roots, and NEW rows aren't in the DB until the run flushes — without
     * this the second sighting would stage a duplicate.
     *
     * @var array<string, string>
     */
    public array $categoryKeyByRemoteId = [];

    /**
     * Tables whose entities produced categories but that carry no `categories`
     * field in the schema. The Resolver flags them here instead of writing a
     * relation to a nonexistent column; the Importer reads them after resolve()
     * to log one notice per table. A set (table => true) so repeated sightings
     * across owners/roots collapse to a single entry.
     *
     * @var array<string, true>
     */
    public array $categoriesFieldMissing = [];

    /**
     * Maximum depth at which the resolver will fetch a transient reference
     * from the upstream API. Root URLs (the ones the Importer hands to the
     * Resolver) are depth 0; references they carry are depth 1. Any
     * transient on a depth-1 row is silently dropped without fetching —
     * the entity persists with whatever scalar fields the parser produced,
     * but its outbound relations stay unset.
     *
     * Bound exists because the upstream graph is densely cross-referenced
     * (each oatour/region links to dozens of POIs which link back to more
     * oatours); without a cap, importing one root would fan out across the
     * whole catalog.
     */
    public const MAX_FETCH_DEPTH = 1;

    /**
     * remote_id → tree depth at which the entity entered the payload. Roots
     * are 0; entities pulled in via a transient on a depth-N row are N+1.
     * Used by the Resolver to decide whether to fetch a transient reference
     * or drop it (see MAX_FETCH_DEPTH).
     *
     * @var array<string, int>
     */
    public array $depthByRemoteId = [];

    /**
     * remote_id → self::STATUS_*. Default-language status map; lifetime
     * equals one Importer::importConfiguration call across every root and
     * every resolver round. See the STATUS_* constants for state semantics.
     *
     * @var array<string, self::STATUS_*>
     */
    public array $defaultStatus = [];

    /**
     * remote_id → sys_language_uid → self::TRANSLATION_*. Translation status
     * map; same lifetime as $defaultStatus. See the TRANSLATION_* constants
     * for state semantics.
     *
     * @var array<string, array<int, self::TRANSLATION_*>>
     */
    public array $translationStatus = [];

    /**
     * URL → failure reason. Suppresses re-fetching across roots, not logging.
     *
     * @var array<string, string>
     */
    public array $failedReferenceUrls = [];

    /**
     * Run-scoped, not per payload: payloads merge first-wins, which would drop
     * a second root's claim on an owner already collected.
     *
     * @var list<CollectedMedia>
     */
    public array $collectedMedia = [];

    /**
     * Download URL → sys_file uid: one fetch per asset per run.
     *
     * @var array<string, int>
     */
    public array $fileUidByDownloadUrl = [];

    /**
     * "<table>|<key>|<field>|<fileUid>" — one reference per file per field,
     * first claim winning.
     *
     * @var array<string, true>
     */
    public array $claimedByField = [];

    /**
     * "<table>|<key>|<field>" whose download failed for a technical reason.
     * Such a field is not reaped: an outage says nothing about the asset.
     *
     * @var array<string, true>
     */
    public array $mediaFailureByField = [];

    /**
     * Download URL → whether its failure means the asset is gone. Needed
     * because the failure cache short-circuits later owners of the same asset,
     * which must still mark their own field.
     *
     * @var array<string, bool>
     */
    public array $assetGoneByUrl = [];

    /**
     * @param array<string, int> $translationLanguages Two-letter language
     *        code → sys_language_uid for every additional site language.
     *        Forwarded to the Parser whenever the Resolver fetches a
     *        transient and reparses it via parseFresh, so fetched entities
     *        (towns, organisations, …) emit translation rows in the same
     *        languages the configured roots do.
     */
    public function __construct(
        public readonly int $storagePid,
        public readonly ParserContext $parserContext,
        public readonly string $language = 'de',
        public readonly ?string $apiKey = null,
        public readonly array $translationLanguages = [],
        public readonly ?Folder $targetFolder = null,
        public readonly ?Folder $stagingFolder = null,
        public readonly int $categoryParentUid = 0,
        public readonly int $categoryStoragePid = 0,
        public readonly ?ImportProgressListener $progressListener = null,
        // Drops media before any API request: both the referenced bucket and
        // inline nodes are discarded unresolved, undownloaded, unrelated.
        public readonly bool $skipMedia = false,
    ) {
    }

    /** Ignored when the owner field already holds the file; first claim wins. */
    public function collectMedia(CollectedMedia $media): void
    {
        $key = implode('|', [$media->ownerTable, $media->ownerKey, $media->targetField, $media->fileUid]);
        if (isset($this->claimedByField[$key])) {
            return;
        }
        $this->claimedByField[$key] = true;
        $this->collectedMedia[] = $media;
    }

    // The run-scoped carrier already; cheaper than a parameter on every method.
    public function reportProgress(string $label): void
    {
        $this->progressListener?->progressed(
            new ImportProgress(ImportPhase::Resolve, $label)
        );
    }

    public function markFound(string $remoteId): void
    {
        if (($this->defaultStatus[$remoteId] ?? null) === self::STATUS_UPDATED) {
            return;
        }
        $this->defaultStatus[$remoteId] = self::STATUS_FOUND;
    }

    public function markUpdated(string $remoteId): void
    {
        $this->defaultStatus[$remoteId] = self::STATUS_UPDATED;
    }

    public function isUpdated(string $remoteId): bool
    {
        return ($this->defaultStatus[$remoteId] ?? null) === self::STATUS_UPDATED;
    }

    // First reason wins; later parents reuse it without re-fetching.
    public function markReferenceFailed(string $url, string $reason): void
    {
        $this->failedReferenceUrls[$url] ??= $reason;
    }

    public function hasReferenceFailed(string $url): bool
    {
        return isset($this->failedReferenceUrls[$url]);
    }

    public function getReferenceFailureReason(string $url): ?string
    {
        return $this->failedReferenceUrls[$url] ?? null;
    }

    public function markTranslationCreated(string $remoteId, int $sysLanguageUid): void
    {
        if (($this->translationStatus[$remoteId][$sysLanguageUid] ?? null) === self::TRANSLATION_UPDATED) {
            return;
        }
        $this->translationStatus[$remoteId][$sysLanguageUid] = self::TRANSLATION_CREATED;
    }

    public function markTranslationUpdated(string $remoteId, int $sysLanguageUid): void
    {
        $this->translationStatus[$remoteId][$sysLanguageUid] = self::TRANSLATION_UPDATED;
    }

    public function isTranslationUpdated(string $remoteId, int $sysLanguageUid): bool
    {
        return ($this->translationStatus[$remoteId][$sysLanguageUid] ?? null) === self::TRANSLATION_UPDATED;
    }

    /**
     * Rewrite NEW… placeholders in $remoteIdToKey to the uids assigned by
     * DataHandler in the previous round. After the call the map only holds
     * uid strings for any remote_id whose row has hit the DB.
     *
     * @param array<string, int|string> $substNEWwithIDs
     */
    public function promoteNewKeys(array $substNEWwithIDs): void
    {
        foreach ($this->remoteIdToKey as $remoteId => $key) {
            if (isset($substNEWwithIDs[$key])) {
                $this->remoteIdToKey[$remoteId] = (string)$substNEWwithIDs[$key];
            }
        }
        foreach ($this->categoryKeyByRemoteId as $remoteId => $key) {
            if (isset($substNEWwithIDs[$key])) {
                $this->categoryKeyByRemoteId[$remoteId] = (string)$substNEWwithIDs[$key];
            }
        }
    }
}
