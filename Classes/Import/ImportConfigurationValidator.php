<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\AnchorScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Service\SitePageIds;

// Pre-flight configuration checks, run once before the import fetches anything
// (alongside the file-folder write probe) so a misconfiguration aborts the run
// instead of being logged per URL.
class ImportConfigurationValidator
{
    public function __construct(
        protected readonly SitePageIds $sitePageIdsResolver,
        protected readonly SysCategoryRepository $sysCategoryRepository,
        protected readonly CategoryAnchorResolver $anchorResolver,
        protected readonly SiteFinder $siteFinder,
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
        $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());

        // Every scope, not only those this run will meet: which kinds a run
        // writes is known only once it has fetched, and a broken scope must
        // abort before that.
        foreach ($this->anchorResolver->scopes() as $scope) {
            $category = $this->anchorResolver->resolveInScope($site, $scope, AnchorKind::Category);
            $this->validateCategoryConfiguration($category->parent, $category->storagePid, $sitePageIds, $scope);
            $keyword = $this->anchorResolver->resolveInScope($site, $scope, AnchorKind::Keyword);
            $this->validateKeywordConfiguration($keyword->parent, $keyword->storagePid, $sitePageIds, $scope);
        }
    }

    /**
     * All page uids within the storagePid's site. Visibility plays no part:
     * see SitePageIds for why enable-fields must not narrow an import scope.
     *
     * @throws StoragePidConfigurationException
     *
     * @return list<int>
     */
    protected function sitePageIds(int $storagePid): array
    {
        try {
            return $this->sitePageIdsResolver->forStoragePid($storagePid);
        } catch (SiteNotFoundException $e) {
            throw new StoragePidConfigurationException(
                'The configured storagePid ' . $storagePid . ' does not belong to any site.',
                1752570000,
                $e
            );
        }
    }

    /**
     * Category mapping is on when either anchor is set; when on it must be
     * complete and both anchors must live inside the storagePid's site.
     *
     * @param list<int> $sitePageIds
     *
     * @throws CategoryConfigurationException
     */
    protected function validateCategoryConfiguration(
        int $parentUid,
        int $storagePid,
        array $sitePageIds,
        AnchorScope $scope
    ): void {
        // Off: no category fields set.
        if ($parentUid === 0 && $storagePid === 0) {
            return;
        }

        // On but incomplete: one anchor set without the other.
        if ($parentUid === 0 || $storagePid === 0) {
            throw new CategoryConfigurationException(
                'Category mapping needs both ' . CategoryAnchorSetting::CategoryParent->settingsPath($scope)
                . ' and ' . CategoryAnchorSetting::CategoryStoragePid->settingsPath($scope)
                . '; got parent=' . $parentUid . ', storage=' . $storagePid . '.',
                1752570001
            );
        }

        if (!in_array($storagePid, $sitePageIds, true)) {
            throw new CategoryConfigurationException(
                CategoryAnchorSetting::CategoryStoragePid->settingsPath($scope) . ' ' . $storagePid
                . ' is outside the storagePid\'s site.',
                1752570002
            );
        }

        $parentPid = $this->sysCategoryRepository->findPid($parentUid);
        if (!in_array($parentPid, $sitePageIds, true)) {
            throw new CategoryConfigurationException(
                CategoryAnchorSetting::CategoryParent->settingsPath($scope) . ' ' . $parentUid
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
    protected function validateKeywordConfiguration(
        int $parentUid,
        int $storagePid,
        array $sitePageIds,
        AnchorScope $scope
    ): void {
        if ($parentUid === 0 && $storagePid === 0) {
            return;
        }

        if ($parentUid === 0 || $storagePid === 0) {
            throw new KeywordConfigurationException(
                'Keyword mapping needs both ' . CategoryAnchorSetting::KeywordParent->settingsPath($scope)
                . ' and ' . CategoryAnchorSetting::KeywordStoragePid->settingsPath($scope)
                . '; got parent=' . $parentUid . ', storage=' . $storagePid . '.',
                1786713820
            );
        }

        if (!in_array($storagePid, $sitePageIds, true)) {
            throw new KeywordConfigurationException(
                CategoryAnchorSetting::KeywordStoragePid->settingsPath($scope) . ' ' . $storagePid
                . ' is outside the storagePid\'s site.',
                1786713821
            );
        }

        $parentPid = $this->sysCategoryRepository->findPid($parentUid);
        if (!in_array($parentPid, $sitePageIds, true)) {
            throw new KeywordConfigurationException(
                CategoryAnchorSetting::KeywordParent->settingsPath($scope) . ' ' . $parentUid
                . ' is outside the storagePid\'s site.',
                1786713822
            );
        }
    }
}
