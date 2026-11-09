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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Settings;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Settings\AnchorScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

final class CategoryAnchorSettingTest extends TestCase
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
        $this->assertSame($expectedPath, $setting->settingsPath(new AnchorScope($scope)));
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
        $this->assertSame($expectedKey, $setting->extensionConfigurationKey(new AnchorScope($scope)));
    }

    /**
     * The two spellings address different configuration levels; a case where
     * they collide would make the resolver read the wrong level.
     */
    #[Test]
    public function bothSpellingsAreDistinctPerCase(): void
    {
        foreach ($this->scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                $this->assertNotSame($setting->settingsPath($scope), $setting->extensionConfigurationKey($scope), $setting->name . ' uses one spelling for both levels.');
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
        foreach ($this->scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                $paths[] = $setting->settingsPath($scope);
                $keys[] = $setting->extensionConfigurationKey($scope);
            }
        }

        $this->assertSame($paths, array_unique($paths), 'Two cases share a site settings path.');
        $this->assertSame($keys, array_unique($keys), 'Two cases share an extension configuration key.');
    }

    /**
     * Every spelling names its scope, so no setting can be read by a record of
     * another one.
     */
    #[Test]
    public function everySpellingCarriesItsScope(): void
    {
        foreach ($this->scopes() as $scope) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                $this->assertStringContainsString('.' . $scope->value . '.', $setting->settingsPath($scope), $setting->name . ' settings path does not name its scope.');
                $this->assertStringContainsString(ucfirst($scope->value), $setting->extensionConfigurationKey($scope), $setting->name . ' extension configuration key does not name its scope.');
            }
        }
    }

    /**
     * @return Iterator<string, array{CategoryAnchorSetting, non-empty-string, string}>
     */
    public static function settingPaths(): Iterator
    {
        yield 'thuecat category storage' => [
            CategoryAnchorSetting::CategoryStoragePid,
            'thuecat',
            'import.thuecat.category.storagePid',
        ];
        yield 'thuecat category parent' => [
            CategoryAnchorSetting::CategoryParent,
            'thuecat',
            'import.thuecat.category.parent',
        ];
        yield 'thuecat keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'thuecat',
            'import.thuecat.keywords.storagePid',
        ];
        yield 'thuecat keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'thuecat',
            'import.thuecat.keywords.parent',
        ];
        yield 'events category storage' => [
            CategoryAnchorSetting::CategoryStoragePid,
            'events',
            'import.events.category.storagePid',
        ];
        yield 'events category parent' => [
            CategoryAnchorSetting::CategoryParent,
            'events',
            'import.events.category.parent',
        ];
        yield 'events keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'events',
            'import.events.keywords.storagePid',
        ];
        yield 'events keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'events',
            'import.events.keywords.parent',
        ];
        yield 'trails keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'trails',
            'import.trails.keywords.storagePid',
        ];
        yield 'trails keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'trails',
            'import.trails.keywords.parent',
        ];
    }

    /**
     * @return Iterator<string, array{CategoryAnchorSetting, non-empty-string, string}>
     */
    public static function extensionConfigurationKeys(): Iterator
    {
        yield 'thuecat category storage' => [
            CategoryAnchorSetting::CategoryStoragePid,
            'thuecat',
            'importThuecatCategoryStoragePid',
        ];
        yield 'thuecat category parent' => [
            CategoryAnchorSetting::CategoryParent,
            'thuecat',
            'importThuecatCategoryParent',
        ];
        yield 'thuecat keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'thuecat',
            'importThuecatKeywordsStoragePid',
        ];
        yield 'thuecat keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'thuecat',
            'importThuecatKeywordsParent',
        ];
        yield 'events category storage' => [
            CategoryAnchorSetting::CategoryStoragePid,
            'events',
            'importEventsCategoryStoragePid',
        ];
        yield 'events category parent' => [
            CategoryAnchorSetting::CategoryParent,
            'events',
            'importEventsCategoryParent',
        ];
        yield 'events keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'events',
            'importEventsKeywordsStoragePid',
        ];
        yield 'events keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'events',
            'importEventsKeywordsParent',
        ];
        yield 'trails keyword storage' => [
            CategoryAnchorSetting::KeywordStoragePid,
            'trails',
            'importTrailsKeywordsStoragePid',
        ];
        yield 'trails keyword parent' => [
            CategoryAnchorSetting::KeywordParent,
            'trails',
            'importTrailsKeywordsParent',
        ];
    }

    /**
     * @return list<AnchorScope>
     */
    private function scopes(): array
    {
        return [new AnchorScope('thuecat'), new AnchorScope('events'), new AnchorScope('trails')];
    }
}
