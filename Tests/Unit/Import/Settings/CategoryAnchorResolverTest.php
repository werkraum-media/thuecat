<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Settings;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;

class CategoryAnchorResolverTest extends TestCase
{
    #[Test]
    public function siteSettingsWinOverExtensionConfiguration(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationReturning(['importThuecatKeywordsParent' => 60]));

        self::assertSame(42, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings(['import.thuecat.keywords.parent' => 42]),
            ImportTarget::Thuecat
        ));
    }

    #[Test]
    public function extensionConfigurationServesAsFallback(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationReturning(['importThuecatKeywordsParent' => 60]));

        self::assertSame(60, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings([]),
            ImportTarget::Thuecat
        ));
    }

    // Unusable site settings don't switch the anchor off; they skip a level.
    #[Test]
    #[DataProvider('unusableValues')]
    public function unusableSiteSettingValueKeepsWalkingTheChain(mixed $unusable): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationReturning(['importThuecatKeywordsParent' => 60]));

        self::assertSame(60, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings(['import.thuecat.keywords.parent' => $unusable]),
            ImportTarget::Thuecat
        ));
    }

    #[Test]
    #[DataProvider('unusableValues')]
    public function unusableExtensionConfigurationValueResolvesToUnset(mixed $unusable): void
    {
        $subject = $this->resolverWith(
            $this->extensionConfigurationReturning(['importThuecatKeywordsParent' => $unusable])
        );

        self::assertSame(0, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings([]),
            ImportTarget::Thuecat
        ));
    }

    #[Test]
    public function nothingConfiguredAnywhereResolvesToUnset(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationNotConfigured());

        self::assertSame(0, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings([]),
            ImportTarget::Thuecat
        ));
    }

    #[Test]
    public function missingExtensionConfigurationKeyResolvesToUnset(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationMissingPath());

        self::assertSame(0, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings([]),
            ImportTarget::Thuecat
        ));
    }

    // Each setting walks the chain on its own, not grouped by kind.
    #[Test]
    public function levelsAreWalkedPerSettingNotPerKind(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationReturning([
            'importThuecatKeywordsStoragePid' => 30,
        ]));
        $site = $this->siteWithSettings(['import.thuecat.keywords.parent' => 42]);

        self::assertSame(42, $subject->resolve(CategoryAnchorSetting::KeywordParent, $site, ImportTarget::Thuecat));
        self::assertSame(30, $subject->resolve(CategoryAnchorSetting::KeywordStoragePid, $site, ImportTarget::Thuecat));
    }

    #[Test]
    public function kindsDoNotBleedIntoEachOther(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationNotConfigured());
        $site = $this->siteWithSettings([
            'import.thuecat.keywords.parent' => 42,
            'import.thuecat.keywords.storagePid' => 30,
        ]);

        self::assertSame(0, $subject->resolve(CategoryAnchorSetting::CategoryParent, $site, ImportTarget::Thuecat));
        self::assertSame(0, $subject->resolve(CategoryAnchorSetting::CategoryStoragePid, $site, ImportTarget::Thuecat));
    }

    /**
     * The reason the settings carry a target at all: one site holding an import
     * of each target keeps two category trees, so a value declared for one
     * target is invisible to the other.
     */
    #[Test]
    public function targetsDoNotBleedIntoEachOther(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationNotConfigured());
        $site = $this->siteWithSettings([
            'import.thuecat.keywords.parent' => 42,
            'import.thuecat.keywords.storagePid' => 30,
        ]);

        self::assertSame(0, $subject->resolve(CategoryAnchorSetting::KeywordParent, $site, ImportTarget::Events));
        self::assertSame(0, $subject->resolve(CategoryAnchorSetting::KeywordStoragePid, $site, ImportTarget::Events));
    }

    #[Test]
    public function eachTargetResolvesItsOwnValue(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationNotConfigured());
        $site = $this->siteWithSettings([
            'import.thuecat.keywords.parent' => 42,
            'import.events.keywords.parent' => 77,
        ]);

        self::assertSame(42, $subject->resolve(CategoryAnchorSetting::KeywordParent, $site, ImportTarget::Thuecat));
        self::assertSame(77, $subject->resolve(CategoryAnchorSetting::KeywordParent, $site, ImportTarget::Events));
    }

    // Nor may the other target's value be borrowed one level down.
    #[Test]
    public function anotherTargetsExtensionConfigurationIsNeverBorrowed(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationReturning([
            'importThuecatKeywordsParent' => 60,
        ]));

        self::assertSame(0, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings([]),
            ImportTarget::Events
        ));
    }

    #[Test]
    public function stringDigitsFromSiteSettingsAreAccepted(): void
    {
        $subject = $this->resolverWith($this->extensionConfigurationNotConfigured());

        self::assertSame(42, $subject->resolve(
            CategoryAnchorSetting::KeywordParent,
            $this->siteWithSettings(['import.thuecat.keywords.parent' => '42']),
            ImportTarget::Thuecat
        ));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableValues(): array
    {
        return [
            'zero' => [0],
            'zero string' => ['0'],
            'empty string' => [''],
            'negative' => [-1],
            'non numeric' => ['not a uid'],
            'null' => [null],
        ];
    }

    // resolve() takes the Site directly; the finder only serves resolveFor().
    private function resolverWith(ExtensionConfiguration $extensionConfiguration): CategoryAnchorResolver
    {
        return new CategoryAnchorResolver($extensionConfiguration, self::createStub(SiteFinder::class));
    }

    /**
     * @param array<string, mixed> $settings dotted keys, as the resolver asks for them
     */
    private function siteWithSettings(array $settings): Site
    {
        $tree = [];
        foreach ($settings as $path => $value) {
            $tree = ArrayUtility::setValueByPath($tree, $path, $value, '.');
        }

        $site = self::createStub(Site::class);
        $site->method('getSettings')->willReturn(SiteSettings::createFromSettingsTree($tree));

        return $site;
    }

    /**
     * @param array<string, mixed> $values keyed by extension configuration key
     */
    private function extensionConfigurationReturning(array $values): ExtensionConfiguration
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnCallback(
            function (string $extension, string $path = '') use ($values) {
                if (!array_key_exists($path, $values)) {
                    throw new ExtensionConfigurationPathDoesNotExistException(
                        'Path ' . $path . ' does not exist.',
                        1_753_000_000
                    );
                }

                return $values[$path];
            }
        );

        return $extensionConfiguration;
    }

    private function extensionConfigurationNotConfigured(): ExtensionConfiguration
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(
            new ExtensionConfigurationExtensionNotConfiguredException('Not configured.', 1_753_000_001)
        );

        return $extensionConfiguration;
    }

    private function extensionConfigurationMissingPath(): ExtensionConfiguration
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(
            new ExtensionConfigurationPathDoesNotExistException('Path missing.', 1_753_000_002)
        );

        return $extensionConfiguration;
    }
}
