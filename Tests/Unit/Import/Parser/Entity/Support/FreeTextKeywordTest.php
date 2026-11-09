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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Support;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\FreeTextKeyword;

final class FreeTextKeywordTest extends TestCase
{
    #[Test]
    public function identityIsLowercasedAndTrimmed(): void
    {
        $this->assertSame('theater', FreeTextKeyword::identity('  Theater '));
    }

    #[Test]
    public function casingVariantsShareOneIdentity(): void
    {
        $this->assertSame(FreeTextKeyword::identity('THEATER'), FreeTextKeyword::identity('Theater'));
    }

    // strtolower() is byte-wise and leaves these untouched, which would yield
    // two rows for one keyword.
    #[Test]
    public function umlautVariantsShareOneIdentity(): void
    {
        $this->assertSame(FreeTextKeyword::identity('Ölmühle'), FreeTextKeyword::identity('ölmühle'));
    }

    #[Test]
    #[DataProvider('titleCases')]
    public function titleIsHumanReadable(string $raw, string $expected): void
    {
        $this->assertSame($expected, FreeTextKeyword::title($raw));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function titleCases(): Iterator
    {
        yield 'lowercase input' => ['erfurt', 'Erfurt'];
        yield 'shouting input' => ['THEATER', 'Theater'];
        yield 'umlaut preserved' => ['ölmühle', 'Ölmühle'];
        // ucwords() would render this 'Electro-pop'.
        yield 'hyphenated keeps both parts' => ['Electro-Pop', 'Electro-Pop'];
        yield 'multi word' => ['sport und freizeit', 'Sport Und Freizeit'];
        yield 'surrounding space dropped' => ['  Kinder  ', 'Kinder'];
    }

    #[Test]
    public function remoteIdCarriesShapeMarker(): void
    {
        $this->assertSame('keyword:text:theater', FreeTextKeyword::remoteId('Theater'));
    }

    // Distinct words must not merge just because casing folded them.
    #[Test]
    public function differentWordsKeepDifferentIdentities(): void
    {
        $this->assertNotSame(FreeTextKeyword::identity('Familie'), FreeTextKeyword::identity('Familien'));
    }
}
