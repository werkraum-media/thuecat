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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Vocabulary;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
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

        $this->assertTrue($index->has('schema:Event'), 'schema.org class missing from merged index');
        $this->assertTrue($index->has('thuecat:BrassMusic'), 'ThueCat class missing from merged index');
    }

    #[Test]
    public function chainResolvesAcrossTheVocabularyBoundary(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        $this->assertSame(['schema:MusicEvent', 'schema:Event'], $index->ancestors('thuecat:BrassMusic'));
    }

    #[Test]
    public function mergedVocabulariesLeaveNoDanglingReferences(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        $this->assertSame([], $index->danglingReferences());
    }

    #[Test]
    public function oneVocabularyAloneLeavesTheOthersReferencesDangling(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::THUECAT]);

        $this->assertSame(['schema:MusicEvent'], $index->danglingReferences());
    }

    #[Test]
    public function labelsSurviveTheMerge(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([self::SCHEMA_ORG, self::THUECAT]);

        $brassMusic = $index->get('thuecat:BrassMusic');
        $this->assertInstanceOf(VocabularyClass::class, $brassMusic);
        $this->assertSame('Blasmusik', $brassMusic->label('de'));
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

        $this->assertInstanceOf(VocabularyClass::class, $museum);
        $this->assertSame(['schema:CivicStructure'], $museum->parents);
    }

    #[Test]
    public function noDocumentsYieldAnEmptyIndex(): void
    {
        $index = (new VocabularyIndexFactory())->fromDocuments([]);

        $this->assertFalse($index->has('schema:Museum'));
        $this->assertSame([], $index->danglingReferences());
    }
}
