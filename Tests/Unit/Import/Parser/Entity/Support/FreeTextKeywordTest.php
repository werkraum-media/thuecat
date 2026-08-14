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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\FreeTextKeyword;

class FreeTextKeywordTest extends TestCase
{
    #[Test]
    public function identityIsLowercasedAndTrimmed(): void
    {
        self::assertSame('theater', FreeTextKeyword::identity('  Theater '));
    }

    #[Test]
    public function casingVariantsShareOneIdentity(): void
    {
        self::assertSame(
            FreeTextKeyword::identity('THEATER'),
            FreeTextKeyword::identity('Theater')
        );
    }

    // strtolower() is byte-wise and leaves these untouched, which would yield
    // two rows for one keyword.
    #[Test]
    public function umlautVariantsShareOneIdentity(): void
    {
        self::assertSame(
            FreeTextKeyword::identity('Ölmühle'),
            FreeTextKeyword::identity('ölmühle')
        );
    }

    #[Test]
    #[DataProvider('titleCases')]
    public function titleIsHumanReadable(string $raw, string $expected): void
    {
        self::assertSame($expected, FreeTextKeyword::title($raw));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function titleCases(): array
    {
        return [
            'lowercase input' => ['erfurt', 'Erfurt'],
            'shouting input' => ['THEATER', 'Theater'],
            'umlaut preserved' => ['ölmühle', 'Ölmühle'],
            // ucwords() would render this 'Electro-pop'.
            'hyphenated keeps both parts' => ['Electro-Pop', 'Electro-Pop'],
            'multi word' => ['sport und freizeit', 'Sport Und Freizeit'],
            'surrounding space dropped' => ['  Kinder  ', 'Kinder'],
        ];
    }

    #[Test]
    public function remoteIdCarriesShapeMarker(): void
    {
        self::assertSame('keyword:text:theater', FreeTextKeyword::remoteId('Theater'));
    }

    // Distinct words must not merge just because casing folded them.
    #[Test]
    public function differentWordsKeepDifferentIdentities(): void
    {
        self::assertNotSame(
            FreeTextKeyword::identity('Familie'),
            FreeTextKeyword::identity('Familien')
        );
    }
}
