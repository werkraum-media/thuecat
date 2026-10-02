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
use WerkraumMedia\ThueCat\Import\Settings\AnchorScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

class CategoryAnchorSettingTest extends TestCase
{
    /**
     * @param non-empty-string $scope
     */
    #[Test]
    #[DataProvider('settingPaths')]
    public function carriesItsSiteSettingsPath(
        CategoryAnchorSetting $setting,
        string $scope,
        string $expectedPath
    ): void {
        self::assertSame($expectedPath, $setting->settingsPath(new AnchorScope($scope)));
    }

    /**
     * @param non-empty-string $scope
     */
    #[Test]
    #[DataProvider('extensionConfigurationKeys')]
    public function carriesItsExtensionConfigurationKey(
        CategoryAnchorSetting $setting,
        string $scope,
        string $expectedKey
    ): void {
        self::assertSame($expectedKey, $setting->extensionConfigurationKey(new AnchorScope($scope)));
    }

    /**
     * The two spellings address different configuration levels; a case where
     * they collide would make the resolver read the wrong level.
     */
    #[Test]
    public function bothSpellingsAreDistinctPerCase(): void
    {
        foreach (self::scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                self::assertNotSame(
                    $setting->settingsPath($scope),
                    $setting->extensionConfigurationKey($scope),
                    $setting->name . ' uses one spelling for both levels.'
                );
            }
        }
    }

    /**
     * Across scopes as well as cases: two scopes sharing a spelling is the
     * collision this whole scoping exists to remove.
     */
    #[Test]
    public function everySpellingIsUsedByExactlyOneCaseAndScope(): void
    {
        $paths = [];
        $keys = [];
        foreach (self::scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                $paths[] = $setting->settingsPath($scope);
                $keys[] = $setting->extensionConfigurationKey($scope);
            }
        }

        self::assertSame($paths, array_unique($paths), 'Two cases share a site settings path.');
        self::assertSame($keys, array_unique($keys), 'Two cases share an extension configuration key.');
    }

    /**
     * Every spelling names its scope, so no setting can be read by a record of
     * another one.
     */
    #[Test]
    public function everySpellingCarriesItsScope(): void
    {
        foreach (self::scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                self::assertStringContainsString(
                    '.' . $scope->value . '.',
                    $setting->settingsPath($scope),
                    $setting->name . ' settings path does not name its scope.'
                );
                self::assertStringContainsString(
                    ucfirst($scope->value),
                    $setting->extensionConfigurationKey($scope),
                    $setting->name . ' extension configuration key does not name its scope.'
                );
            }
        }
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, non-empty-string, string}>
     */
    public static function settingPaths(): array
    {
        return [
            'thuecat category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                'thuecat',
                'import.thuecat.category.storagePid',
            ],
            'thuecat category parent' => [
                CategoryAnchorSetting::CategoryParent,
                'thuecat',
                'import.thuecat.category.parent',
            ],
            'thuecat keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'thuecat',
                'import.thuecat.keywords.storagePid',
            ],
            'thuecat keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'thuecat',
                'import.thuecat.keywords.parent',
            ],
            'events category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                'events',
                'import.events.category.storagePid',
            ],
            'events category parent' => [
                CategoryAnchorSetting::CategoryParent,
                'events',
                'import.events.category.parent',
            ],
            'events keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'events',
                'import.events.keywords.storagePid',
            ],
            'events keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'events',
                'import.events.keywords.parent',
            ],
            'trails keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'trails',
                'import.trails.keywords.storagePid',
            ],
            'trails keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'trails',
                'import.trails.keywords.parent',
            ],
        ];
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, non-empty-string, string}>
     */
    public static function extensionConfigurationKeys(): array
    {
        return [
            'thuecat category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                'thuecat',
                'importThuecatCategoryStoragePid',
            ],
            'thuecat category parent' => [
                CategoryAnchorSetting::CategoryParent,
                'thuecat',
                'importThuecatCategoryParent',
            ],
            'thuecat keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'thuecat',
                'importThuecatKeywordsStoragePid',
            ],
            'thuecat keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'thuecat',
                'importThuecatKeywordsParent',
            ],
            'events category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                'events',
                'importEventsCategoryStoragePid',
            ],
            'events category parent' => [
                CategoryAnchorSetting::CategoryParent,
                'events',
                'importEventsCategoryParent',
            ],
            'events keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'events',
                'importEventsKeywordsStoragePid',
            ],
            'events keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'events',
                'importEventsKeywordsParent',
            ],
            'trails keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                'trails',
                'importTrailsKeywordsStoragePid',
            ],
            'trails keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                'trails',
                'importTrailsKeywordsParent',
            ],
        ];
    }

    /**
     * @return list<AnchorScope>
     */
    private static function scopes(): array
    {
        return [new AnchorScope('thuecat'), new AnchorScope('events'), new AnchorScope('trails')];
    }
}
