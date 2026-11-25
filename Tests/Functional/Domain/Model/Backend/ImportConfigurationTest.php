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

namespace WerkraumMedia\ThueCat\Tests\Functional\Domain\Model\Backend;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportConfigurationTestCase;

final class ImportConfigurationTest extends AbstractImportConfigurationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportConfigurationColumns.csv');
    }

    #[Test]
    public function readsTheSharedSettingsFromTheirColumns(): void
    {
        $configuration = $this->fetchImportConfiguration(1);

        $this->assertSame(10, $configuration->getStoragePid());
        $this->assertSame('1:/thuecat/static/', $configuration->getFileFolder());
        $this->assertSame('static-key', $configuration->getApiKey());
        $this->assertSame(120, $configuration->getMessageBudget());
        $this->assertSame(600, $configuration->getFetchCacheLifetime());
    }

    #[Test]
    public function readsTheUrlsFromItsUrlRecordsInOrder(): void
    {
        $this->assertSame(
            [
                'https://thuecat.org/resources/test-first',
                'https://thuecat.org/resources/test-second',
                'https://thuecat.org/resources/test-third',
            ],
            $this->fetchImportConfiguration(1)->getUrls(),
        );
    }

    #[Test]
    public function readsTheSyncScopeSettingsFromTheirColumns(): void
    {
        $configuration = $this->fetchImportConfiguration(2);

        $this->assertSame('test-scope', $configuration->getSyncScopeId());
        $this->assertSame(6, $configuration->getFetchLastXDays());
    }

    #[Test]
    public function readsTheContainsPlaceIdFromItsColumn(): void
    {
        $this->assertSame('test-place', $this->fetchImportConfiguration(3)->getContainsPlaceId());
    }

    #[Test]
    public function readsTheSyncChannelSettingsFromTheirColumns(): void
    {
        $configuration = $this->fetchImportConfiguration(4);

        $this->assertSame(13, $configuration->getStoragePid());
        $this->assertSame('test-channel', $configuration->getSyncChannelId());
        $this->assertSame('channel-key', $configuration->getApiKey());
    }

    #[Test]
    public function aConfiguredTunableWinsOverTheExtensionConfiguration(): void
    {
        $this->writeExtensionConfiguration(['messageBudget' => '50', 'fetchCacheLifetime' => '60']);
        $configuration = $this->fetchImportConfiguration(1);
        $settings = $this->get(ImportSettings::class);

        $this->assertSame(120, $settings->resolve(ImportSetting::MessageBudget, $configuration->getMessageBudget()));
        $this->assertSame(600, $settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime()));
    }

    #[Test]
    public function anEmptyTunableFallsBackToTheExtensionConfiguration(): void
    {
        $this->writeExtensionConfiguration(['messageBudget' => '50', 'fetchCacheLifetime' => '60']);
        $configuration = $this->fetchImportConfiguration(2);
        $settings = $this->get(ImportSettings::class);

        $this->assertSame(50, $settings->resolve(ImportSetting::MessageBudget, $configuration->getMessageBudget()));
        $this->assertSame(60, $settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime()));
    }
}
