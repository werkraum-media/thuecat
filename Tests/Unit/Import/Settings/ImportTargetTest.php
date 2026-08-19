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
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;

class ImportTargetTest extends TestCase
{
    #[Test]
    #[DataProvider('flexFormValues')]
    public function carriesItsFlexFormValue(ImportTarget $target, string $expectedValue): void
    {
        self::assertSame($expectedValue, $target->value);
    }

    #[Test]
    #[DataProvider('flexFormValues')]
    public function parsesItsOwnFlexFormValue(ImportTarget $expected, string $value): void
    {
        self::assertSame($expected, ImportTarget::tryFromConfigured($value));
    }

    /**
     * Configurations predating the field import ThueCat POI structures by
     * definition, so nothing supplied means thuecat rather than unknown.
     */
    #[Test]
    public function treatsAbsentValueAsThuecat(): void
    {
        self::assertSame(ImportTarget::Thuecat, ImportTarget::tryFromConfigured(''));
    }

    /**
     * Unknown stays inspectable: null lets the validator report the value it
     * found. Defaulting here would switch every anchor off silently.
     */
    #[Test]
    #[DataProvider('unknownValues')]
    public function reportsUnknownValueAsUnparsed(string $value): void
    {
        self::assertNull(ImportTarget::tryFromConfigured($value));
    }

    #[Test]
    public function listsAcceptedValuesForErrorMessages(): void
    {
        self::assertSame(['thuecat', 'events'], ImportTarget::configuredValues());
    }

    /**
     * @return array<string, array{ImportTarget, string}>
     */
    public static function flexFormValues(): array
    {
        return [
            'thuecat' => [ImportTarget::Thuecat, 'thuecat'],
            'events' => [ImportTarget::Events, 'events'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unknownValues(): array
    {
        return [
            'unknown extension key' => ['future'],
            'wrong case' => ['ThueCat'],
            'padded' => [' thuecat'],
        ];
    }
}
