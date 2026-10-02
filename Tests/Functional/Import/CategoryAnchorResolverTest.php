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
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\AnchorPair;
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
    public function fallsBackToExtensionConfigurationWithoutSiteSettings(): void
    {
        $this->writeSiteSettings([], 'anchors', 300);
        $this->writeExtensionConfiguration([
            'importThuecatKeywordsParent' => '110',
            'importThuecatKeywordsStoragePid' => '330',
        ]);

        $pair = $this->resolvePair('tx_thuecat_tourist_attraction', AnchorKind::Keyword);

        self::assertSame(110, $pair->parent);
        self::assertSame(330, $pair->storagePid);
        self::assertSame('thuecat', $pair->scope?->value);
    }

    // Two sites must not see each other's anchors.
    #[Test]
    public function resolvesTheAnchorsOfTheGivenSite(): void
    {
        $this->writeSiteSettings([
            'import' => ['thuecat' => ['keywords' => ['parent' => 110, 'storagePid' => 330]]],
        ], 'anchors', 300);
        $this->writeSiteSettings([
            'import' => ['thuecat' => ['keywords' => ['parent' => 910, 'storagePid' => 930]]],
        ], 'other_anchors', 900);

        $pair = $this->resolvePair('tx_thuecat_tourist_attraction', AnchorKind::Keyword, 910);

        self::assertSame(910, $pair->parent);
        self::assertSame(930, $pair->storagePid);
    }

    #[Test]
    public function recordKindResolvesItsOwnScope(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['keywords' => ['storagePid' => 330, 'parent' => 110]],
                'trails' => ['keywords' => ['storagePid' => 360, 'parent' => 140]],
            ],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(140, $pair->parent);
        self::assertSame(360, $pair->storagePid);
        self::assertSame('trails', $pair->scope?->value);
    }

    #[Test]
    public function attractionResolvesTheThuecatScope(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['category' => ['storagePid' => 320, 'parent' => 100]],
                'events' => ['category' => ['storagePid' => 340, 'parent' => 120]],
            ],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_tourist_attraction', AnchorKind::Category);

        self::assertSame(100, $pair->parent);
        self::assertSame(320, $pair->storagePid);
        self::assertSame('thuecat', $pair->scope?->value);
    }

    /**
     * A trail run without trail settings keeps today's behaviour.
     */
    #[Test]
    public function unconfiguredTrailsScopeFallsBackToThuecat(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['keywords' => ['storagePid' => 330, 'parent' => 110]],
                'events' => ['keywords' => ['storagePid' => 350, 'parent' => 130]],
            ],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(110, $pair->parent);
        self::assertSame(330, $pair->storagePid);
        self::assertSame('thuecat', $pair->scope?->value);
    }

    #[Test]
    public function unconfiguredEventsScopeFallsBackToThuecat(): void
    {
        $this->writeSiteSettings([
            'import' => ['thuecat' => ['category' => ['storagePid' => 320, 'parent' => 100]]],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_events_domain_model_event', AnchorKind::Category);

        self::assertSame(100, $pair->parent);
        self::assertSame('thuecat', $pair->scope?->value);
    }

    /**
     * Relation kinds are shared between imports of every top-level kind, so
     * another scope's settings never apply to them.
     */
    #[Test]
    public function relationKindResolvesThuecatWhateverElseIsConfigured(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['keywords' => ['storagePid' => 330, 'parent' => 110]],
                'events' => ['keywords' => ['storagePid' => 350, 'parent' => 130]],
                'trails' => ['keywords' => ['storagePid' => 360, 'parent' => 140]],
            ],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_tourist_information', AnchorKind::Keyword);

        self::assertSame(110, $pair->parent);
        self::assertSame('thuecat', $pair->scope?->value);
    }

    #[Test]
    public function chainResolvesUnsetWhenNoScopeSuppliesAnything(): void
    {
        $this->writeSiteSettings([], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(0, $pair->parent);
        self::assertSame(0, $pair->storagePid);
        self::assertNull($pair->scope);
    }

    #[Test]
    public function levelsAreWalkedPerSettingWithinAScope(): void
    {
        $this->writeSiteSettings([
            'import' => ['trails' => ['keywords' => ['parent' => 140]]],
        ], 'anchors', 300);
        $this->writeExtensionConfiguration([
            'importTrailsKeywordsStoragePid' => '360',
        ]);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(140, $pair->parent);
        self::assertSame(360, $pair->storagePid);
        self::assertSame('trails', $pair->scope?->value);
    }

    /**
     * Half a pair still claims the kind for its scope; completing it from
     * another scope would pair a trail parent with a ThueCat folder.
     */
    #[Test]
    public function scopeIsNeverCompletedFromAnotherScope(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['keywords' => ['storagePid' => 330, 'parent' => 110]],
                'trails' => ['keywords' => ['parent' => 140]],
            ],
        ], 'anchors', 300);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(140, $pair->parent);
        self::assertSame(0, $pair->storagePid);
        self::assertSame('trails', $pair->scope?->value);
    }

    #[Test]
    public function zeroInSiteSettingsFallsThroughToExtensionConfiguration(): void
    {
        $this->writeSiteSettings([
            'import' => ['trails' => ['keywords' => ['storagePid' => 0, 'parent' => 0]]],
        ], 'anchors', 300);
        $this->writeExtensionConfiguration([
            'importTrailsKeywordsParent' => '140',
            'importTrailsKeywordsStoragePid' => '360',
        ]);

        $pair = $this->resolvePair('tx_thuecat_trail', AnchorKind::Keyword);

        self::assertSame(140, $pair->parent);
        self::assertSame(360, $pair->storagePid);
        self::assertSame('trails', $pair->scope?->value);
    }

    private function resolvePair(string $table, AnchorKind $kind, int $pageId = 310): AnchorPair
    {
        return $this->get(CategoryAnchorResolver::class)->resolvePair(
            $this->get(SiteFinder::class)->getSiteByPageId($pageId),
            $table,
            $kind
        );
    }
}
