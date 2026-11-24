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

use Throwable;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;

readonly class EffectiveSettingsReport
{
    public function __construct(
        protected CategoryAnchorResolver $anchorResolver,
        protected ImportSettings $settings,
        protected ImportLogger $importLogger,
    ) {
    }

    /**
     * What actually governs this run, assembled once before the first fetch.
     * The values come from three levels (import configuration, site settings,
     * extension configuration), so a run that behaves unexpectedly can be
     * explained without tracing the chain by hand.
     *
     * There is deliberately no apiKey entry: excluding it by construction
     * leaves no masking step to forget and no rendering path that could leak
     * it. Reporting is wrapped because it is diagnostics, not a gate — a
     * failure here must not abort an otherwise healthy run.
     *
     * Anchors are listed for every scope, because which kinds a run writes is
     * known only once it has fetched.
     *
     * @return array<string, string|int> what was recorded, nothing when reporting failed
     */
    public function record(ImportConfigurationInterface $configuration, Site $site): array
    {
        try {
            $settings = [
                'storagePid' => $configuration->getStoragePid(),
                'fileFolder' => $configuration->getFileFolder(),
            ];
            // What each scope supplies itself; which one a record falls back
            // to is decided per record and has no single value to report.
            foreach ($this->anchorResolver->scopes() as $scope) {
                foreach (AnchorKind::cases() as $kind) {
                    $pair = $this->anchorResolver->resolveInScope($site, $scope, $kind);
                    $settings[$kind->parentSetting()->settingsPath($scope)] = $pair->parent > 0 ? $pair->parent : 'unset';
                    $settings[$kind->storagePidSetting()->settingsPath($scope)] = $pair->storagePid > 0 ? $pair->storagePid : 'unset';
                }
            }

            $settings += [
                'readTimeout' => $this->settings->resolve(ImportSetting::ReadTimeout, 0),
                'connectTimeout' => $this->settings->resolve(ImportSetting::ConnectTimeout, 0),
                'maxAttempts' => $this->settings->resolve(ImportSetting::MaxAttempts, 0),
                'runBudget' => $this->settings->resolve(ImportSetting::RunBudget, $configuration->getRunBudget()),
                'fetchCacheLifetime' => $this->settings->resolve(
                    ImportSetting::FetchCacheLifetime,
                    $configuration->getFetchCacheLifetime()
                ),
            ];

            $this->importLogger->recordEffectiveSettings($settings);

            return $settings;
        } catch (Throwable) {
            // Diagnostics must never cost a run.
            return [];
        }
    }
}
