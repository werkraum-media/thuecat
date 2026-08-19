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
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;

class CategoryAnchorSettingTest extends TestCase
{
    #[Test]
    #[DataProvider('settingPaths')]
    public function carriesItsSiteSettingsPath(
        CategoryAnchorSetting $setting,
        ImportTarget $target,
        string $expectedPath
    ): void {
        self::assertSame($expectedPath, $setting->settingsPath($target));
    }

    #[Test]
    #[DataProvider('extensionConfigurationKeys')]
    public function carriesItsExtensionConfigurationKey(
        CategoryAnchorSetting $setting,
        ImportTarget $target,
        string $expectedKey
    ): void {
        self::assertSame($expectedKey, $setting->extensionConfigurationKey($target));
    }

    /**
     * The two spellings address different configuration levels; a case where
     * they collide would make the resolver read the wrong level.
     */
    #[Test]
    public function bothSpellingsAreDistinctPerCase(): void
    {
        foreach (ImportTarget::cases() as $target) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                self::assertNotSame(
                    $setting->settingsPath($target),
                    $setting->extensionConfigurationKey($target),
                    $setting->name . ' uses one spelling for both levels.'
                );
            }
        }
    }

    /**
     * Across targets as well as cases: two targets sharing a spelling is the
     * collision this whole scoping exists to remove.
     */
    #[Test]
    public function everySpellingIsUsedByExactlyOneCaseAndTarget(): void
    {
        $paths = [];
        $keys = [];
        foreach (ImportTarget::cases() as $target) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                $paths[] = $setting->settingsPath($target);
                $keys[] = $setting->extensionConfigurationKey($target);
            }
        }

        self::assertSame($paths, array_unique($paths), 'Two cases share a site settings path.');
        self::assertSame($keys, array_unique($keys), 'Two cases share an extension configuration key.');
    }

    /**
     * Every spelling names its target, so no setting can be read by an import
     * of the other one.
     */
    #[Test]
    public function everySpellingCarriesItsTarget(): void
    {
        foreach (ImportTarget::cases() as $target) {
            foreach (CategoryAnchorSetting::cases() as $setting) {
                self::assertStringContainsString(
                    '.' . $target->value . '.',
                    $setting->settingsPath($target),
                    $setting->name . ' settings path does not name its target.'
                );
                self::assertStringContainsString(
                    ucfirst($target->value),
                    $setting->extensionConfigurationKey($target),
                    $setting->name . ' extension configuration key does not name its target.'
                );
            }
        }
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, ImportTarget, string}>
     */
    public static function settingPaths(): array
    {
        return [
            'thuecat category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                ImportTarget::Thuecat,
                'import.thuecat.category.storagePid',
            ],
            'thuecat category parent' => [
                CategoryAnchorSetting::CategoryParent,
                ImportTarget::Thuecat,
                'import.thuecat.category.parent',
            ],
            'thuecat keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                ImportTarget::Thuecat,
                'import.thuecat.keywords.storagePid',
            ],
            'thuecat keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                ImportTarget::Thuecat,
                'import.thuecat.keywords.parent',
            ],
            'events category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                ImportTarget::Events,
                'import.events.category.storagePid',
            ],
            'events category parent' => [
                CategoryAnchorSetting::CategoryParent,
                ImportTarget::Events,
                'import.events.category.parent',
            ],
            'events keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                ImportTarget::Events,
                'import.events.keywords.storagePid',
            ],
            'events keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                ImportTarget::Events,
                'import.events.keywords.parent',
            ],
        ];
    }

    /**
     * @return array<string, array{CategoryAnchorSetting, ImportTarget, string}>
     */
    public static function extensionConfigurationKeys(): array
    {
        return [
            'thuecat category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                ImportTarget::Thuecat,
                'importThuecatCategoryStoragePid',
            ],
            'thuecat category parent' => [
                CategoryAnchorSetting::CategoryParent,
                ImportTarget::Thuecat,
                'importThuecatCategoryParent',
            ],
            'thuecat keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                ImportTarget::Thuecat,
                'importThuecatKeywordsStoragePid',
            ],
            'thuecat keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                ImportTarget::Thuecat,
                'importThuecatKeywordsParent',
            ],
            'events category storage' => [
                CategoryAnchorSetting::CategoryStoragePid,
                ImportTarget::Events,
                'importEventsCategoryStoragePid',
            ],
            'events category parent' => [
                CategoryAnchorSetting::CategoryParent,
                ImportTarget::Events,
                'importEventsCategoryParent',
            ],
            'events keyword storage' => [
                CategoryAnchorSetting::KeywordStoragePid,
                ImportTarget::Events,
                'importEventsKeywordsStoragePid',
            ],
            'events keyword parent' => [
                CategoryAnchorSetting::KeywordParent,
                ImportTarget::Events,
                'importEventsKeywordsParent',
            ],
        ];
    }
}
