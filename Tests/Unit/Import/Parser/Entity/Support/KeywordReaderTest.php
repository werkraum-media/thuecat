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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\KeywordEntry;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\KeywordReader;

class KeywordReaderTest extends TestCase
{
    #[Test]
    public function returnsNothingWhenPropertyAbsent(): void
    {
        self::assertSame([], $this->read([]));
    }

    #[Test]
    public function returnsNothingWhenPropertyIsNull(): void
    {
        self::assertSame([], $this->read(['schema:keywords' => null]));
    }

    #[Test]
    public function readsSingleReferenceNotWrappedInList(): void
    {
        $entries = $this->read([
            'schema:keywords' => ['@id' => 'https://thuecat.org/resources/475728955106-qdcc'],
        ]);

        self::assertCount(1, $entries);
        self::assertSame(KeywordEntry::SHAPE_REFERENCE, $entries[0]->shape);
        self::assertSame('https://thuecat.org/resources/475728955106-qdcc', $entries[0]->value);
    }

    #[Test]
    public function readsSingleLiteralNotWrappedInList(): void
    {
        $entries = $this->read([
            'schema:keywords' => ['@language' => 'de', '@value' => 'Theater'],
        ]);

        self::assertCount(1, $entries);
        self::assertSame(KeywordEntry::SHAPE_FREE_TEXT, $entries[0]->shape);
        self::assertSame('Theater', $entries[0]->value);
    }

    #[Test]
    public function distinguishesTypedLiteralFromFreeText(): void
    {
        $entries = $this->read([
            'schema:keywords' => [
                ['@type' => 'thuecat:ConventionLocationTopics', '@value' => 'thuecat:Urban'],
                ['@language' => 'de', '@value' => 'Theater'],
            ],
        ]);

        self::assertSame(KeywordEntry::SHAPE_ONTOLOGY, $entries[0]->shape);
        self::assertSame('thuecat:Urban', $entries[0]->value);
        // The usage-site type decides the parent; the fetched term cannot.
        self::assertSame('thuecat:ConventionLocationTopics', $entries[0]->usageType);

        self::assertSame(KeywordEntry::SHAPE_FREE_TEXT, $entries[1]->shape);
        self::assertNull($entries[1]->usageType);
    }

    // Shapes coexist in one root; none may be dropped because of another.
    #[Test]
    public function readsAllThreeShapesMixedInOneRoot(): void
    {
        $entries = $this->read([
            'schema:keywords' => [
                ['@id' => 'https://thuecat.org/resources/475728955106-qdcc'],
                ['@type' => 'thuecat:PoiTopic', '@value' => 'thuecat:KeywordSport'],
                ['@language' => 'de', '@value' => 'Krimifestival'],
            ],
        ]);

        self::assertCount(3, $entries);
        self::assertSame(
            [
                KeywordEntry::SHAPE_REFERENCE,
                KeywordEntry::SHAPE_ONTOLOGY,
                KeywordEntry::SHAPE_FREE_TEXT,
            ],
            array_map(static fn (KeywordEntry $e): string => $e->shape, $entries)
        );
    }

    // A datatype is not a vocabulary: xsd:string stays free text.
    #[Test]
    public function treatsDatatypedLiteralAsFreeText(): void
    {
        $entries = $this->read([
            'schema:keywords' => ['@type' => 'xsd:string', '@value' => 'Sommer'],
        ]);

        self::assertCount(1, $entries);
        self::assertSame(KeywordEntry::SHAPE_FREE_TEXT, $entries[0]->shape);
        self::assertNull($entries[0]->usageType);
    }

    #[Test]
    public function skipsEntriesWithoutUsableValue(): void
    {
        $entries = $this->read([
            'schema:keywords' => [
                ['@value' => ''],
                ['@id' => ''],
                ['unrelated' => 'x'],
                ['@language' => 'de', '@value' => 'Theater'],
            ],
        ]);

        self::assertCount(1, $entries);
        self::assertSame('Theater', $entries[0]->value);
    }

    #[Test]
    public function keepsDuplicateReferencesOnlyOnce(): void
    {
        $entries = $this->read([
            'schema:keywords' => [
                ['@id' => 'https://thuecat.org/resources/475728955106-qdcc'],
                ['@id' => 'https://thuecat.org/resources/475728955106-qdcc'],
            ],
        ]);

        self::assertCount(1, $entries);
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return list<KeywordEntry>
     */
    private function read(array $node): array
    {
        return (new KeywordReader())->read($node);
    }
}
