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
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

class CategoryAnchorSettingTest extends TestCase
{
    #[Test]
    #[DataProvider('settingPaths')]
    public function carriesItsSiteSettingsPath(
        CategoryAnchorSetting $setting,
        string $expectedPath
    ): void {
        self::assertSame($expectedPath, $setting->settingsPath());
    }

    #[Test]
    #[DataProvider('extensionConfigurationKeys')]
    public function carriesItsExtensionConfigurationKey(
        CategoryAnchorSetting $setting,
        string $expectedKey
    ): void {
        self::assertSame($expectedKey, $setting->extensionConfigurationKey());
    }

    /**
     * The two spellings address different configuration levels; a case where
     * they collide would make the resolver read the wrong level.
     */
    #[Test]
    public function bothSpellingsAreDistinctPerCase(): void
    {
        foreach (CategoryAnchorSetting::cases() as $setting) {
            self::assertNotSame(
                $setting->settingsPath(),
                $setting->extensionConfigurationKey(),
                $setting->name . ' uses one spelling for both levels.'
            );
        }
    }

    #[Test]
    public function everySpellingIsUsedByExactlyOneCase(): void
    {
        $paths = [];
        $keys = [];
        foreach (CategoryAnchorSetting::cases() as $setting) {
            $paths[] = $setting->settingsPath();
            $keys[] = $setting->extensionConfigurationKey();
        }

        self::assertSame($paths, array_unique($paths), 'Two cases share a site settings path.');
        self::assertSame($keys, array_unique($keys), 'Two cases share an extension configuration key.');
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, string}>
     */
    public static function settingPaths(): array
    {
        return [
            'category storage' => [CategoryAnchorSetting::CategoryStoragePid, 'import.category.storagePid'],
            'category parent' => [CategoryAnchorSetting::CategoryParent, 'import.category.parent'],
            'keyword storage' => [CategoryAnchorSetting::KeywordStoragePid, 'import.keywords.storagePid'],
            'keyword parent' => [CategoryAnchorSetting::KeywordParent, 'import.keywords.parent'],
        ];
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, string}>
     */
    public static function extensionConfigurationKeys(): array
    {
        return [
            'category storage' => [CategoryAnchorSetting::CategoryStoragePid, 'importCategoryStoragePid'],
            'category parent' => [CategoryAnchorSetting::CategoryParent, 'importCategoryParent'],
            'keyword storage' => [CategoryAnchorSetting::KeywordStoragePid, 'importKeywordsStoragePid'],
            'keyword parent' => [CategoryAnchorSetting::KeywordParent, 'importKeywordsParent'],
        ];
    }
}
