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
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\LocalisedValueReader;

class LocalisedValueReaderTest extends TestCase
{
    #[Test]
    public function returnsMatchingEntryFromListOfLanguageVariants(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'Am Horn 1'],
            ['@language' => 'en', '@value' => 'At the Horn 1'],
        ];

        $this->assertSame('At the Horn 1', (new LocalisedValueReader())->read($value, 'en'));
    }

    #[Test]
    public function returnsEmptyStringWhenNoListEntryMatches(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'Am Horn 1'],
            ['@language' => 'en', '@value' => 'At the Horn 1'],
        ];

        $this->assertSame('', (new LocalisedValueReader())->read($value, 'fr'));
    }

    #[Test]
    public function returnsFirstMatchWhenListRepeatsALanguage(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'first'],
            ['@language' => 'de', '@value' => 'second'],
        ];

        $this->assertSame('first', (new LocalisedValueReader())->read($value, 'de'));
    }

    #[Test]
    public function returnsValueFromSingleMatchingObject(): void
    {
        $value = ['@language' => 'de', '@value' => 'Am Horn 1'];

        $this->assertSame('Am Horn 1', (new LocalisedValueReader())->read($value, 'de'));
    }

    #[Test]
    public function returnsEmptyStringWhenSingleObjectDoesNotMatch(): void
    {
        $value = ['@language' => 'de', '@value' => 'Am Horn 1'];

        $this->assertSame('', (new LocalisedValueReader())->read($value, 'en'));
    }

    /** The fallback that must not be lost: typed enums carry no language tag. */
    #[Test]
    public function fallsBackToUntaggedTypedValue(): void
    {
        $value = ['@type' => 'schema:Text', '@value' => 'thuecat:CC0'];

        $this->assertSame('thuecat:CC0', (new LocalisedValueReader())->read($value, 'en'));
    }

    #[Test]
    public function fallsBackToBareValueObject(): void
    {
        $this->assertSame('99425', (new LocalisedValueReader())->read(['@value' => '99425'], 'de'));
    }

    /** The concatenated path feeds bare strings through per member. */
    #[Test]
    public function returnsBareScalarUnchanged(): void
    {
        $this->assertSame('thuecat:Streetcar', (new LocalisedValueReader())->read('thuecat:Streetcar', 'de'));
    }

    #[Test]
    public function fallsBackToUntaggedEntryInsideAList(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'Am Horn 1'],
            ['@type' => 'schema:Text', '@value' => 'untagged'],
        ];

        $this->assertSame('untagged', (new LocalisedValueReader())->read($value, 'fr'));
    }

    #[Test]
    #[DataProvider('unusableValues')]
    public function returnsEmptyStringForUnusableInput(mixed $value): void
    {
        $this->assertSame('', (new LocalisedValueReader())->read($value, 'de'));
    }

    /** @return Iterator<string, array{mixed}> */
    public static function unusableValues(): Iterator
    {
        yield 'null' => [null];
        yield 'empty array' => [[]];
        yield 'empty string' => [''];
        yield 'false' => [false];
        yield 'integer' => [42];
        yield 'object without @value' => [['@language' => 'de']];
        yield 'nested array @value' => [['@value' => ['unexpected']]];
        yield 'list of non arrays' => [[null, false]];
    }
}
