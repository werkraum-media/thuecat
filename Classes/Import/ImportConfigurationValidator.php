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

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

// Pre-flight configuration checks, run once before the import fetches anything
// (alongside the file-folder write probe) so a misconfiguration aborts the run
// instead of being logged per URL.
class ImportConfigurationValidator
{
    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly PageRepository $pageRepository,
        protected readonly SysCategoryRepository $sysCategoryRepository,
        protected readonly CategoryAnchorResolver $anchorResolver,
    ) {
    }

    /**
     * @throws StoragePidConfigurationException storagePid maps to no site
     * @throws CategoryConfigurationException category mapping on but unusable
     * @throws KeywordConfigurationException keyword mapping on but unusable
     */
    public function validate(ImportConfigurationInterface $configuration): void
    {
        $sitePageIds = $this->sitePageIds($configuration->getStoragePid());
        $anchors = $this->anchorResolver->resolveFor($configuration);
        $this->validateCategoryConfiguration(
            $anchors->categoryParent,
            $anchors->categoryStoragePid,
            $sitePageIds
        );
        $this->validateKeywordConfiguration(
            $anchors->keywordParent,
            $anchors->keywordStoragePid,
            $sitePageIds
        );
    }

    /**
     * All page uids within the storagePid's site.
     *
     *
     * @throws StoragePidConfigurationException
     *
     * @return list<int>
     */
    protected function sitePageIds(int $storagePid): array
    {
        try {
            $rootPageId = $this->siteFinder->getSiteByPageId($storagePid)->getRootPageId();
        } catch (SiteNotFoundException $e) {
            throw new StoragePidConfigurationException(
                'The configured storagePid ' . $storagePid . ' does not belong to any site.',
                1752570000,
                $e
            );
        }

        return array_values($this->pageRepository->getPageIdsRecursive([$rootPageId], 99));
    }

    /**
     * Category mapping is on when either anchor is set; when on it must be
     * complete and both anchors must live inside the storagePid's site.
     *
     * @param list<int> $sitePageIds
     *
     * @throws CategoryConfigurationException
     */
    protected function validateCategoryConfiguration(int $parentUid, int $storagePid, array $sitePageIds): void
    {
        // Off: no category fields set.
        if ($parentUid === 0 && $storagePid === 0) {
            return;
        }

        // On but incomplete: one anchor set without the other.
        if ($parentUid === 0 || $storagePid === 0) {
            throw new CategoryConfigurationException(
                'Category mapping needs both ' . CategoryAnchorSetting::CategoryParent->settingsPath()
                . ' and ' . CategoryAnchorSetting::CategoryStoragePid->settingsPath()
                . '; got parent=' . $parentUid . ', storage=' . $storagePid . '.',
                1752570001
            );
        }

        if (!in_array($storagePid, $sitePageIds, true)) {
            throw new CategoryConfigurationException(
                CategoryAnchorSetting::CategoryStoragePid->settingsPath() . ' ' . $storagePid
                . ' is outside the storagePid\'s site.',
                1752570002
            );
        }

        $parentPid = $this->sysCategoryRepository->findPid($parentUid);
        if (!in_array($parentPid, $sitePageIds, true)) {
            throw new CategoryConfigurationException(
                CategoryAnchorSetting::CategoryParent->settingsPath() . ' ' . $parentUid
                . ' is outside the storagePid\'s site.',
                1752570003
            );
        }
    }

    /**
     * Same rules as the category anchors, evaluated independently: keywords are
     * a separate property, so one being configured says nothing about the other.
     *
     * @param list<int> $sitePageIds
     *
     * @throws KeywordConfigurationException
     */
    protected function validateKeywordConfiguration(int $parentUid, int $storagePid, array $sitePageIds): void
    {
        if ($parentUid === 0 && $storagePid === 0) {
            return;
        }

        if ($parentUid === 0 || $storagePid === 0) {
            throw new KeywordConfigurationException(
                'Keyword mapping needs both ' . CategoryAnchorSetting::KeywordParent->settingsPath()
                . ' and ' . CategoryAnchorSetting::KeywordStoragePid->settingsPath()
                . '; got parent=' . $parentUid . ', storage=' . $storagePid . '.',
                1786713820
            );
        }

        if (!in_array($storagePid, $sitePageIds, true)) {
            throw new KeywordConfigurationException(
                CategoryAnchorSetting::KeywordStoragePid->settingsPath() . ' ' . $storagePid
                . ' is outside the storagePid\'s site.',
                1786713821
            );
        }

        $parentPid = $this->sysCategoryRepository->findPid($parentUid);
        if (!in_array($parentPid, $sitePageIds, true)) {
            throw new KeywordConfigurationException(
                CategoryAnchorSetting::KeywordParent->settingsPath() . ' ' . $parentUid
                . ' is outside the storagePid\'s site.',
                1786713822
            );
        }
    }
}
