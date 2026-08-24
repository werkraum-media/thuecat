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

        self::assertSame('At the Horn 1', (new LocalisedValueReader())->read($value, 'en'));
    }

    #[Test]
    public function returnsEmptyStringWhenNoListEntryMatches(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'Am Horn 1'],
            ['@language' => 'en', '@value' => 'At the Horn 1'],
        ];

        self::assertSame('', (new LocalisedValueReader())->read($value, 'fr'));
    }

    #[Test]
    public function returnsFirstMatchWhenListRepeatsALanguage(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'first'],
            ['@language' => 'de', '@value' => 'second'],
        ];

        self::assertSame('first', (new LocalisedValueReader())->read($value, 'de'));
    }

    #[Test]
    public function returnsValueFromSingleMatchingObject(): void
    {
        $value = ['@language' => 'de', '@value' => 'Am Horn 1'];

        self::assertSame('Am Horn 1', (new LocalisedValueReader())->read($value, 'de'));
    }

    #[Test]
    public function returnsEmptyStringWhenSingleObjectDoesNotMatch(): void
    {
        $value = ['@language' => 'de', '@value' => 'Am Horn 1'];

        self::assertSame('', (new LocalisedValueReader())->read($value, 'en'));
    }

    /** The fallback that must not be lost: typed enums carry no language tag. */
    #[Test]
    public function fallsBackToUntaggedTypedValue(): void
    {
        $value = ['@type' => 'schema:Text', '@value' => 'thuecat:CC0'];

        self::assertSame('thuecat:CC0', (new LocalisedValueReader())->read($value, 'en'));
    }

    #[Test]
    public function fallsBackToBareValueObject(): void
    {
        self::assertSame('99425', (new LocalisedValueReader())->read(['@value' => '99425'], 'de'));
    }

    /** The concatenated path feeds bare strings through per member. */
    #[Test]
    public function returnsBareScalarUnchanged(): void
    {
        self::assertSame('thuecat:Streetcar', (new LocalisedValueReader())->read('thuecat:Streetcar', 'de'));
    }

    #[Test]
    public function fallsBackToUntaggedEntryInsideAList(): void
    {
        $value = [
            ['@language' => 'de', '@value' => 'Am Horn 1'],
            ['@type' => 'schema:Text', '@value' => 'untagged'],
        ];

        self::assertSame('untagged', (new LocalisedValueReader())->read($value, 'fr'));
    }

    #[Test]
    #[DataProvider('unusableValues')]
    public function returnsEmptyStringForUnusableInput(mixed $value): void
    {
        self::assertSame('', (new LocalisedValueReader())->read($value, 'de'));
    }

    /** @return array<string, array{mixed}> */
    public static function unusableValues(): array
    {
        return [
            'null' => [null],
            'empty array' => [[]],
            'empty string' => [''],
            'false' => [false],
            'integer' => [42],
            'object without @value' => [['@language' => 'de']],
            'nested array @value' => [['@value' => ['unexpected']]],
            'list of non arrays' => [[null, false]],
        ];
    }
}
