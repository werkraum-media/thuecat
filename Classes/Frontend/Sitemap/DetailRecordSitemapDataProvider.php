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

namespace WerkraumMedia\ThueCat\Frontend\Sitemap;

use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKinds;

/**
 * The XML sitemap of one detail record kind, configured by `config.kind` alone.
 *
 * Table, show page and plugin argument come from the kind and the current
 * site. Records are taken from the site's page tree only, so storage pids need
 * no configuration. A site naming no show page for the kind gets an empty
 * sitemap.
 */
class DetailRecordSitemapDataProvider extends RecordsXmlSitemapDataProvider
{
    /** Extbase's "infinite" recursion depth. */
    protected const RECURSIVE = 250;

    public function generateItems(): void
    {
        $site = $this->request->getAttribute('site');
        if (!$site instanceof Site) {
            return;
        }

        $kinds = GeneralUtility::makeInstance(DetailRecordKinds::class);
        $identifier = $this->config['kind'] ?? '';
        $kind = $kinds->get(is_string($identifier) ? $identifier : '');
        $showPageUid = $kinds->showPageUid($kind, $site);
        if ($showPageUid === 0) {
            return;
        }

        $pluginNamespace = GeneralUtility::makeInstance(ExtensionService::class)
            ->getPluginNamespace($kind->extensionName, $kind->pluginName)
        ;

        $this->config['table'] = $kind->table;
        $this->config['pid'] = $site->getRootPageId();
        $this->config['recursive'] = self::RECURSIVE;
        $url = is_array($this->config['url'] ?? null) ? $this->config['url'] : [];
        $url['pageId'] = $showPageUid;
        $url['fieldToParameterMap'] = ['uid' => $pluginNamespace . '[' . $kind->argumentName . ']'];
        $this->config['url'] = $url;

        // DataHandler flushes the table tag on every change, so the sitemap need not wait for its cache period.
        $this->request->getAttribute('frontend.cache.collector')?->addCacheTags(new CacheTag($kind->table));

        parent::generateItems();
    }
}
