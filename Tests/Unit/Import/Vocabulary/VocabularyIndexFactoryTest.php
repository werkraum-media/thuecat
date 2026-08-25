<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Vocabulary;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexFactory;

class VocabularyIndexFactoryTest extends TestCase
{
    private const SCHEMA_ORG = [
        '@graph' => [
            [
                '@id' => 'schema:MusicEvent',
                '@type' => 'rdfs:Class',
                'rdfs:label' => 'MusicEvent',
                'rdfs:subClassOf' => ['@id' => 'schema:Event'],
            ],
            ['@id' => 'schema:Event', '@type' => 'rdfs:Class', 'rdfs:label' => 'Event'],
        ],
    ];

    private const THUECAT = [
        '@graph' => [
            [
                '@id' => 'thuecat:BrassMusic',
                '@type' => ['rdfs:Class'],
                'rdfs:label' => [['@language' => 'de', '@value' => 'Blasmusik']],
                'rdfs:subClassOf' => ['@id' => 'schema:MusicEvent'],
            ],
        ],
    ];

    #[Test]
    public function mergesBothVocabulariesIntoOneIndex(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        self::assertTrue($index->has('schema:Event'), 'schema.org class missing from merged index');
        self::assertTrue($index->has('thuecat:BrassMusic'), 'ThueCat class missing from merged index');
    }

    #[Test]
    public function chainResolvesAcrossTheVocabularyBoundary(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        self::assertSame(
            ['schema:MusicEvent', 'schema:Event'],
            $index->ancestors('thuecat:BrassMusic')
        );
    }

    #[Test]
    public function mergedVocabulariesLeaveNoDanglingReferences(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        self::assertSame([], $index->danglingReferences());
    }

    #[Test]
    public function oneVocabularyAloneLeavesTheOthersReferencesDangling(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::THUECAT]);

        self::assertSame(['schema:MusicEvent'], $index->danglingReferences());
    }

    #[Test]
    public function labelsSurviveTheMerge(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        $brassMusic = $index->get('thuecat:BrassMusic');
        self::assertNotNull($brassMusic);
        self::assertSame('Blasmusik', $brassMusic->label('de'));
    }

    #[Test]
    public function laterDocumentWinsOnARedeclaredClass(): void
    {
        $first = ['@graph' => [
            ['@id' => 'schema:Museum', '@type' => 'rdfs:Class', 'rdfs:label' => 'First'],
        ]];
        $second = ['@graph' => [
            [
                '@id' => 'schema:Museum',
                '@type' => 'rdfs:Class',
                'rdfs:label' => 'Second',
                'rdfs:subClassOf' => ['@id' => 'schema:CivicStructure'],
            ],
        ]];

        $index = (new VocabularyIndexFactory())->fromDocuments([$first, $second]);
        $museum = $index->get('schema:Museum');

        self::assertNotNull($museum);
        self::assertSame(['schema:CivicStructure'], $museum->parents);
    }

    #[Test]
    public function noDocumentsYieldAnEmptyIndex(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([]);

        self::assertFalse($index->has('schema:Museum'));
        self::assertSame([], $index->danglingReferences());
    }
}
