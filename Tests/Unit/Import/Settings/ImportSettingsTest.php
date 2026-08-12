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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;

class ImportSettingsTest extends TestCase
{
    #[Test]
    public function configurationValueWinsOverExtensionConfiguration(): void
    {
        $subject = new ImportSettings($this->extensionConfigurationReturning('60'));

        self::assertSame(15, $subject->resolve(ImportSetting::ReadTimeout, 15));
    }

    #[Test]
    public function emptyConfigurationValueFallsBackToExtensionConfiguration(): void
    {
        $subject = new ImportSettings($this->extensionConfigurationReturning('60'));

        self::assertSame(60, $subject->resolve(ImportSetting::ReadTimeout, ''));
    }

    #[Test]
    public function missingExtensionConfigurationValueFallsBackToDefault(): void
    {
        $subject = new ImportSettings($this->extensionConfigurationReturning(''));

        self::assertSame(
            ImportSetting::ReadTimeout->default(),
            $subject->resolve(ImportSetting::ReadTimeout, '')
        );
    }

    #[Test]
    public function unconfiguredExtensionFallsBackToDefault(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')
            ->willThrowException(new ExtensionConfigurationExtensionNotConfiguredException())
        ;

        $subject = new ImportSettings($extensionConfiguration);

        self::assertSame(
            ImportSetting::MaxAttempts->default(),
            $subject->resolve(ImportSetting::MaxAttempts, '')
        );
    }

    /**
     * 0 is Guzzle's "unlimited" and also what an empty flexform number field
     * yields. Unlimited is the bug this change removes, so it must never be
     * configurable.
     */
    #[Test]
    #[DataProvider('unsetValues')]
    public function unsetValueFallsBackRatherThanMeaningUnlimited(mixed $configurationValue): void
    {
        $subject = new ImportSettings($this->extensionConfigurationReturning('45'));

        self::assertSame(45, $subject->resolve(ImportSetting::ReadTimeout, $configurationValue));
    }

    public static function unsetValues(): array
    {
        return [
            'empty string' => [''],
            'integer zero' => [0],
            'string zero' => ['0'],
        ];
    }

    #[Test]
    public function extensionConfigurationZeroIsAlsoTreatedAsUnset(): void
    {
        $subject = new ImportSettings($this->extensionConfigurationReturning('0'));

        self::assertSame(
            ImportSetting::RunBudget->default(),
            $subject->resolve(ImportSetting::RunBudget, '')
        );
    }

    #[Test]
    public function eachSettingReadsItsOwnExtensionConfigurationKey(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnCallback(
            fn (string $extension, string $path): string => $path === 'connectTimeout' ? '7' : '99'
        );

        $subject = new ImportSettings($extensionConfiguration);

        self::assertSame(7, $subject->resolve(ImportSetting::ConnectTimeout, ''));
    }

    private function extensionConfigurationReturning(string $value): ExtensionConfiguration
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($value);

        return $extensionConfiguration;
    }
}
