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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\CurieExpander;

class CurieExpanderTest extends TestCase
{
    #[Test]
    public function expandsKnownPrefixToOntologyUri(): void
    {
        self::assertSame(
            'https://thuecat.org/ontology/thuecat/1.0/Urban',
            (new CurieExpander())->expand('thuecat:Urban')
        );
    }

    #[Test]
    public function returnsNullForUnknownPrefix(): void
    {
        self::assertNull((new CurieExpander())->expand('schema:Urban'));
    }

    #[Test]
    public function returnsNullWhenNoPrefixPresent(): void
    {
        self::assertNull((new CurieExpander())->expand('Urban'));
    }

    #[Test]
    public function returnsNullWhenLocalPartEmpty(): void
    {
        self::assertNull((new CurieExpander())->expand('thuecat:'));
    }

    #[Test]
    public function expandsRegardlessOfLocalPartCasing(): void
    {
        self::assertSame(
            'https://thuecat.org/ontology/thuecat/1.0/KeywordSportAndLeisure',
            (new CurieExpander())->expand('thuecat:KeywordSportAndLeisure')
        );
    }
}
