<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;

/**
 * Records predating the new flexform fields must run unchanged on fallbacks.
 *
 * The fixture's flexform deliberately carries none of them; adding them there
 * would destroy what this file tests.
 */
class LegacyImportConfigurationTest extends AbstractImportTestCase
{
    #[Test]
    public function aRecordWithoutTheNewFieldsReportsThemAsUnset(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $configuration = $this->getConfiguration(1);

        self::assertSame(0, $configuration->getRunBudget(), 'A missing flexform field is "not set", not a value.');
        self::assertSame(0, $configuration->getFetchCacheLifetime());
    }

    #[Test]
    public function theUnsetFieldsResolveToTheCodeDefaults(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $configuration = $this->getConfiguration(1);
        $settings = $this->get(ImportSettings::class);
        self::assertInstanceOf(ImportSettings::class, $settings);

        self::assertSame(
            ImportSetting::RunBudget->default(),
            $settings->resolve(ImportSetting::RunBudget, $configuration->getRunBudget()),
            'The run is bounded by the default budget, not by 0 (unbounded).'
        );
        self::assertSame(
            ImportSetting::FetchCacheLifetime->default(),
            $settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime())
        );
    }

    #[Test]
    public function suchARecordImportsWithoutEditing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('debug', $severity, 'A legacy record runs clean on fallback values.');
        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    protected function getConfiguration(int $uid): ImportConfigurationInterface
    {
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');

        return $configuration;
    }
}
