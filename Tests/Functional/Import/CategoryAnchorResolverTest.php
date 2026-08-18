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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportConfigurationTestCase;

// Resolution against a real site, reached through the import's storagePid.
class CategoryAnchorResolverTest extends AbstractImportConfigurationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/CategoryAnchorPreState.php');
    }

    #[Test]
    public function resolvesAnchorsFromTheSiteOwningTheStoragePid(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'category' => ['storagePid' => 320, 'parent' => 100],
                'keywords' => ['storagePid' => 330, 'parent' => 110],
            ],
        ], 'anchors', 300);

        $anchors = $this->get(CategoryAnchorResolver::class)->resolveFor($this->configurationWithStoragePid(310));

        self::assertSame(100, $anchors->categoryParent);
        self::assertSame(320, $anchors->categoryStoragePid);
        self::assertSame(110, $anchors->keywordParent);
        self::assertSame(330, $anchors->keywordStoragePid);
    }

    #[Test]
    public function fallsBackToExtensionConfigurationWithoutSiteSettings(): void
    {
        $this->writeSiteSettings([], 'anchors', 300);
        $this->writeExtensionConfiguration([
            'importKeywordsParent' => '110',
            'importKeywordsStoragePid' => '330',
        ]);

        $anchors = $this->get(CategoryAnchorResolver::class)->resolveFor($this->configurationWithStoragePid(310));

        self::assertSame(110, $anchors->keywordParent);
        self::assertSame(330, $anchors->keywordStoragePid);
        self::assertSame(0, $anchors->categoryParent);
        self::assertSame(0, $anchors->categoryStoragePid);
    }

    #[Test]
    public function resolvesToUnsetWithoutAnyConfiguration(): void
    {
        $this->writeSiteSettings([], 'anchors', 300);

        $anchors = $this->get(CategoryAnchorResolver::class)->resolveFor($this->configurationWithStoragePid(310));

        self::assertSame(0, $anchors->categoryParent);
        self::assertSame(0, $anchors->categoryStoragePid);
        self::assertSame(0, $anchors->keywordParent);
        self::assertSame(0, $anchors->keywordStoragePid);
    }

    // Two sites must not see each other's anchors.
    #[Test]
    public function resolvesTheAnchorsOfTheImportsOwnSite(): void
    {
        $this->writeSiteSettings([
            'import' => ['keywords' => ['parent' => 110, 'storagePid' => 330]],
        ], 'anchors', 300);
        $this->writeSiteSettings([
            'import' => ['keywords' => ['parent' => 910, 'storagePid' => 930]],
        ], 'other_anchors', 900);

        $anchors = $this->get(CategoryAnchorResolver::class)->resolveFor($this->configurationWithStoragePid(910));

        self::assertSame(910, $anchors->keywordParent);
        self::assertSame(930, $anchors->keywordStoragePid);
    }

    private function configurationWithStoragePid(int $storagePid): ImportConfigurationInterface
    {
        $configuration = self::createStub(ImportConfigurationInterface::class);
        $configuration->method('getStoragePid')->willReturn($storagePid);

        return $configuration;
    }
}
