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
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportConfigurationTestCase;

// Covers the writeSiteSettings() helper itself: the anchor tests are only
// meaningful if a test can give a site its own settings.
class SiteSettingsHelperTest extends AbstractImportConfigurationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/SiteSettingsHelperPreState.php');
    }

    #[Test]
    public function writtenSettingsAreReadableFromTheSite(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'keywords' => [
                    'parent' => 100,
                    'storagePid' => 30,
                ],
            ],
        ], 'settings_helper', 200);

        $settings = $this->get(SiteFinder::class)->getSiteByPageId(210)->getSettings();

        self::assertSame(100, $settings->get('import.keywords.parent'));
        self::assertSame(30, $settings->get('import.keywords.storagePid'));
    }

    #[Test]
    public function siteWithoutWrittenSettingsHasNoAnchors(): void
    {
        $this->writeSiteSettings([], 'settings_helper', 200);

        $settings = $this->get(SiteFinder::class)->getSiteByPageId(210)->getSettings();

        self::assertNull($settings->get('import.keywords.parent'));
    }

    // The import suite reaches its site fixtures through a symlink, so a write
    // that resolves to the source tree leaves committed files behind.
    #[Test]
    public function committedSiteFixturesAreNotTouched(): void
    {
        $fixtures = __DIR__ . '/../Fixtures/Import/Sites';
        $before = scandir($fixtures);

        $this->writeSiteSettings([
            'import' => ['keywords' => ['parent' => 100]],
        ], 'settings_helper', 200);

        self::assertSame($before, scandir($fixtures));
    }
}
