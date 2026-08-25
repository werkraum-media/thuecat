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

namespace WerkraumMedia\ThueCat\Import\SysCategory;

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;

/**
 * Turns source values into persisted, parented, translated `sys_category`
 * records for any field backed by that table — type categories, keywords and
 * whatever else comes to be stored there — whatever produced the values.
 *
 * The anchor and identifier prefix are inputs rather than assumptions, so two
 * consumers sharing this service keep their trees, identifiers and per-run
 * deduplication apart.
 */
class SysCategoryProvisioner
{
    public function __construct(
        protected readonly SysCategoryRepository $sysCategoryRepository
    ) {
    }

    /**
     * The datamap key of the term for one source value, creating it and any
     * translations it needs. Returns null where the value has no usable title
     * and must be skipped.
     *
     * @param list<int>         $sitePageIds
     * @param array<string,int> $translationLanguages language code => sys_language_uid
     */
    public function provision(
        DataHandlerPayload $payload,
        SysCategoryProvisioningState $state,
        SysCategoryAnchor $anchor,
        SysCategoryTerm $term,
        array $sitePageIds,
        string $defaultLanguage,
        array $translationLanguages
    ): ?string {
        $identifier = $anchor->prefixed($term->sourceValue);

        $known = $state->keyFor($identifier);
        if ($known !== null) {
            return $known;
        }
        if ($state->wasSkipped($identifier)) {
            return null;
        }

        $key = $this->findOrStage(
            $payload,
            $state,
            $anchor,
            $term,
            $identifier,
            $sitePageIds,
            $defaultLanguage
        );
        if ($key === null) {
            $state->markSkipped($identifier, $term->parentValue);

            return null;
        }

        $state->remember($identifier, $key);
        $this->stageTranslations($payload, $term, $identifier, $translationLanguages);

        return $key;
    }

    /**
     * Staged against the identifier rather than the datamap key, because the
     * later translation pass resolves rows by remote_id and a NEW… key means
     * nothing to it yet.
     *
     * A language the term has no title for is left alone: filling it with the
     * default language's title would present an untranslated row as a choice.
     *
     * @param array<string,int> $translationLanguages
     */
    protected function stageTranslations(
        DataHandlerPayload $payload,
        SysCategoryTerm $term,
        string $identifier,
        array $translationLanguages
    ): void {
        foreach ($translationLanguages as $language => $sysLanguageUid) {
            $title = $term->titleFor($language);
            if ($title === null) {
                continue;
            }

            $payload->addTranslationField(
                'sys_category',
                $identifier,
                $sysLanguageUid,
                'title',
                $title
            );
        }
    }

    /**
     * @param list<int> $sitePageIds
     */
    protected function findOrStage(
        DataHandlerPayload $payload,
        SysCategoryProvisioningState $state,
        SysCategoryAnchor $anchor,
        SysCategoryTerm $term,
        string $identifier,
        array $sitePageIds,
        string $defaultLanguage
    ): ?string {
        $stored = $this->findStoredUid($anchor->parentUid, $sitePageIds, $identifier);
        if ($stored > 0) {
            $this->reParent($payload, $state, $anchor, $term, $stored);

            return (string)$stored;
        }

        $title = $term->titleFor($defaultLanguage);
        if ($title === null) {
            return null;
        }

        $key = StringUtility::getUniqueId('NEW');
        $payload->addRow('sys_category', $key, [
            'pid' => $anchor->storagePid,
            'parent' => $this->parentKey($state, $anchor, $term),
            'title' => $title,
            'remote_id' => $identifier,
        ]);

        return $key;
    }

    /**
     * Move a stored term under the parent it now belongs to, without replacing
     * it: its uid is referenced by configuration elsewhere, so a new record
     * carrying the same meaning would still be the wrong record.
     *
     * Staged only where the parent actually differs, so an unchanged re-import
     * writes nothing. A parent still resolving to a NEW… key cannot be compared
     * against a stored uid, and is written as the move it is.
     */
    protected function reParent(
        DataHandlerPayload $payload,
        SysCategoryProvisioningState $state,
        SysCategoryAnchor $anchor,
        SysCategoryTerm $term,
        int $uid
    ): void {
        $parentKey = $this->parentKey($state, $anchor, $term);
        $isStoredParent = MathUtility::canBeInterpretedAsInteger($parentKey);

        if ($isStoredParent && (int)$parentKey === $this->sysCategoryRepository->findParent($uid)) {
            return;
        }

        // Merged rather than set: addRow() replaces the whole entry, and this
        // uid may already carry fields staged elsewhere in the run.
        $existing = $payload->getDataMap()['sys_category'][(string)$uid] ?? [];
        $payload->addRow('sys_category', (string)$uid, array_merge(
            is_array($existing) ? $existing : [],
            ['parent' => $parentKey]
        ));
    }

    /**
     * The nearest level that actually exists. A skipped term costs its own
     * level only, so the climb continues through it rather than dropping the
     * whole subtree onto the anchor.
     *
     * A parent this run never saw at all also lands on the anchor: it is either
     * outside the chain or was cut off above it.
     */
    protected function parentKey(
        SysCategoryProvisioningState $state,
        SysCategoryAnchor $anchor,
        SysCategoryTerm $term
    ): string {
        $parentValue = $term->parentValue;
        $seen = [];

        while ($parentValue !== null) {
            $identifier = $anchor->prefixed($parentValue);
            if (isset($seen[$identifier])) {
                break;
            }
            $seen[$identifier] = true;

            $key = $state->keyFor($identifier);
            if ($key !== null) {
                return $key;
            }
            if (!$state->wasSkipped($identifier)) {
                break;
            }

            $parentValue = $state->parentOfSkipped($identifier);
        }

        return (string)$anchor->parentUid;
    }

    /**
     * Matched by identifier so an editor's rename survives, and accepted only
     * with the anchor in its rootline: a match under a different parent belongs
     * to another tree.
     *
     * @param list<int> $sitePageIds
     */
    protected function findStoredUid(int $parentUid, array $sitePageIds, string $identifier): int
    {
        foreach ($this->sysCategoryRepository->findUidsByRemoteId($identifier, $sitePageIds) as $uid) {
            if ($this->hasParentInRootline($uid, $parentUid)) {
                return $uid;
            }
        }

        return 0;
    }

    protected function hasParentInRootline(int $uid, int $ancestorUid): bool
    {
        $seen = [];
        $current = $uid;
        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            $current = $this->sysCategoryRepository->findParent($current);

            if ($current === $ancestorUid) {
                return true;
            }
        }

        return false;
    }
}
