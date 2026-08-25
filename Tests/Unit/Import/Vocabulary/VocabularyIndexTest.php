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
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * Chains below mirror the published vocabularies, where ThueCat extends
 * schema.org, so an ancestry walk crosses from one into the other.
 */
class VocabularyIndexTest extends TestCase
{
    /**
     * @param array<string, list<string>> $parentsById
     */
    private function index(array $parentsById): VocabularyIndex
    {
        $classes = [];
        foreach ($parentsById as $id => $parents) {
            $classes[$id] = new VocabularyClass($id, $parents, []);
        }

        return new VocabularyIndex($classes);
    }

    #[Test]
    public function knowsWhichClassesItHolds(): void
    {
        $index = $this->index(['schema:Museum' => []]);

        self::assertTrue($index->has('schema:Museum'));
        self::assertFalse($index->has('schema:Absent'));
    }

    #[Test]
    public function returnsNullForAnUnknownClass(): void
    {
        self::assertNull($this->index([])->get('schema:Absent'));
    }

    #[Test]
    public function exposesDeclaredParents(): void
    {
        $index = $this->index([
            'thuecat:Archive' => ['schema:CivicStructure', 'schema:Museum'],
        ]);

        self::assertSame(
            ['schema:CivicStructure', 'schema:Museum'],
            $index->parents('thuecat:Archive')
        );
    }

    #[Test]
    public function unknownClassHasNoParents(): void
    {
        self::assertSame([], $this->index([])->parents('schema:Absent'));
    }

    #[Test]
    public function ancestryCrossesFromOneVocabularyIntoTheOther(): void
    {
        // thuecat:BrassMusic declares a schema.org parent upstream.
        $index = $this->index([
            'thuecat:BrassMusic' => ['schema:MusicEvent'],
            'schema:MusicEvent' => ['schema:Event'],
            'schema:Event' => ['schema:Thing'],
            'schema:Thing' => [],
        ]);

        self::assertSame(
            ['schema:MusicEvent', 'schema:Event', 'schema:Thing'],
            $index->ancestors('thuecat:BrassMusic')
        );
    }

    #[Test]
    public function ancestryExcludesTheClassItself(): void
    {
        $index = $this->index([
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => [],
        ]);

        self::assertNotContains('schema:Museum', $index->ancestors('schema:Museum'));
    }

    #[Test]
    public function ancestryVisitsEveryBranchOfSeveralParents(): void
    {
        $index = $this->index([
            'thuecat:Cinema' => ['schema:EntertainmentBusiness', 'schema:EventVenue'],
            'schema:EntertainmentBusiness' => ['schema:LocalBusiness'],
            'schema:EventVenue' => ['schema:Place'],
            'schema:LocalBusiness' => [],
            'schema:Place' => [],
        ]);

        $ancestors = $index->ancestors('thuecat:Cinema');

        self::assertContains('schema:EntertainmentBusiness', $ancestors);
        self::assertContains('schema:EventVenue', $ancestors);
        self::assertContains('schema:LocalBusiness', $ancestors);
        self::assertContains('schema:Place', $ancestors);
    }

    #[Test]
    public function ancestryReportsEachAncestorOnceWhenBranchesReconverge(): void
    {
        $index = $this->index([
            'thuecat:Split' => ['schema:Left', 'schema:Right'],
            'schema:Left' => ['schema:Thing'],
            'schema:Right' => ['schema:Thing'],
            'schema:Thing' => [],
        ]);

        $ancestors = $index->ancestors('thuecat:Split');

        self::assertSame(['schema:Thing'], array_values(array_filter(
            $ancestors,
            static fn (string $id): bool => $id === 'schema:Thing'
        )));
    }

    #[Test]
    public function ancestryTerminatesOnACyclicChain(): void
    {
        $index = $this->index([
            'thuecat:A' => ['thuecat:B'],
            'thuecat:B' => ['thuecat:A'],
        ]);

        self::assertSame(['thuecat:B', 'thuecat:A'], $index->ancestors('thuecat:A'));
    }

    #[Test]
    public function ancestryTerminatesOnASelfReference(): void
    {
        $index = $this->index(['thuecat:Loop' => ['thuecat:Loop']]);

        self::assertSame(['thuecat:Loop'], $index->ancestors('thuecat:Loop'));
    }

    #[Test]
    public function ancestryStopsAtAnUndeclaredParent(): void
    {
        $index = $this->index(['thuecat:Orphan' => ['ttgds:Unknown']]);

        self::assertSame(['ttgds:Unknown'], $index->ancestors('thuecat:Orphan'));
    }

    #[Test]
    public function reportsReferencesNoVocabularyDeclares(): void
    {
        $index = $this->index([
            'thuecat:Orphan' => ['ttgds:Unknown'],
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => [],
        ]);

        self::assertSame(['ttgds:Unknown'], $index->danglingReferences());
    }

    #[Test]
    public function healthyVocabulariesLeaveNoDanglingReferences(): void
    {
        $index = $this->index([
            'thuecat:BrassMusic' => ['schema:MusicEvent'],
            'schema:MusicEvent' => ['schema:Event'],
            'schema:Event' => [],
        ]);

        self::assertSame([], $index->danglingReferences());
    }
}
