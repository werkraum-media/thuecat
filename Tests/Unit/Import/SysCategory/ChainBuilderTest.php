<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SysCategory;

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
use WerkraumMedia\ThueCat\Import\SysCategory\ChainBuilder;
use WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategy;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * Chains below mirror the published vocabularies. `thuecat:Archive` and
 * `thuecat:Cinema` in particular are real: the first restates its own chain
 * through two parents, the second genuinely branches.
 */
class ChainBuilderTest extends TestCase
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

    private function strategy(): RecordingParentStrategy
    {
        return new RecordingParentStrategy();
    }

    /**
     * @param array<string, list<string>> $parentsById
     *
     * @return list<string>
     */
    private function build(array $parentsById, string $type, ?ParentStrategy $strategy = null): array
    {
        return (new ChainBuilder())->build(
            $this->index($parentsById),
            $type,
            $strategy ?? $this->strategy()
        );
    }

    #[Test]
    public function ordersAncestorsAboveTheTypeItself(): void
    {
        $chain = $this->build([
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => ['schema:Place'],
            'schema:Place' => ['schema:Thing'],
            'schema:Thing' => [],
        ], 'schema:Museum');

        self::assertSame(['schema:CivicStructure', 'schema:Museum'], $chain);
    }

    #[Test]
    public function keepsATypeWithNoAncestorsAtAll(): void
    {
        $chain = $this->build(['thuecat:Solo' => []], 'thuecat:Solo');

        self::assertSame(['thuecat:Solo'], $chain);
    }

    #[Test]
    public function buildsNothingForATypeTheIndexDoesNotKnow(): void
    {
        $chain = $this->build(['schema:Museum' => []], 'ttgds:Unknown');

        self::assertSame([], $chain);
    }

    #[Test]
    public function terminatesOnACyclicChain(): void
    {
        $chain = $this->build([
            'thuecat:A' => ['thuecat:B'],
            'thuecat:B' => ['thuecat:A'],
        ], 'thuecat:A');

        self::assertContains('thuecat:A', $chain);
        self::assertLessThanOrEqual(2, count($chain), 'A cycle must not repeat a class.');
    }

    #[Test]
    public function cutsOffClassesEveryRecordBelongsTo(): void
    {
        $chain = $this->build([
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => ['schema:Place'],
            'schema:Place' => ['schema:Thing'],
            'schema:Thing' => [],
        ], 'schema:Museum');

        self::assertNotContains('schema:Thing', $chain);
        self::assertNotContains('schema:Place', $chain);
    }

    #[Test]
    public function keepsEveryClassBelowTheCutoff(): void
    {
        $chain = $this->build([
            'thuecat:BeerBar' => ['schema:BarOrPub'],
            'schema:BarOrPub' => ['schema:FoodEstablishment'],
            'schema:FoodEstablishment' => ['schema:LocalBusiness'],
            'schema:LocalBusiness' => ['schema:Place'],
            'schema:Place' => ['schema:Thing'],
            'schema:Thing' => [],
        ], 'thuecat:BeerBar');

        self::assertSame(
            ['schema:LocalBusiness', 'schema:FoodEstablishment', 'schema:BarOrPub', 'thuecat:BeerBar'],
            $chain
        );
    }

    #[Test]
    public function aTypeLeftWithoutAncestorsByTheCutoffBecomesARoot(): void
    {
        $chain = $this->build([
            'schema:CivicStructure' => ['schema:Place'],
            'schema:Place' => ['schema:Thing'],
            'schema:Thing' => [],
        ], 'schema:CivicStructure');

        self::assertSame(['schema:CivicStructure'], $chain);
    }

    #[Test]
    public function dropsAParentThatMerelyRestatesTheChain(): void
    {
        // thuecat:Archive names CivicStructure and Museum; Museum is itself a
        // CivicStructure, so naming both is one chain, not a fork.
        $chain = $this->build([
            'thuecat:Archive' => ['schema:CivicStructure', 'schema:Museum'],
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => ['schema:Place'],
            'schema:Place' => [],
        ], 'thuecat:Archive');

        self::assertSame(
            ['schema:CivicStructure', 'schema:Museum', 'thuecat:Archive'],
            $chain,
            'The nearer parent wins, and the restated ancestor keeps its own level.'
        );
    }

    #[Test]
    public function doesNotConsultTheStrategyForARestatedChain(): void
    {
        $strategy = $this->strategy();

        $this->build([
            'thuecat:Archive' => ['schema:CivicStructure', 'schema:Museum'],
            'schema:Museum' => ['schema:CivicStructure'],
            'schema:CivicStructure' => [],
        ], 'thuecat:Archive', $strategy);

        self::assertSame([], $strategy->asked, 'Reduction settles this without a choice.');
    }

    #[Test]
    public function asksTheStrategyWhenParentsGenuinelyBranch(): void
    {
        $strategy = $this->strategy();

        // thuecat:Cinema is an EntertainmentBusiness and an EventVenue;
        // neither is an ancestor of the other.
        $this->build([
            'thuecat:Cinema' => ['schema:EntertainmentBusiness', 'schema:EventVenue'],
            'schema:EntertainmentBusiness' => [],
            'schema:EventVenue' => [],
        ], 'thuecat:Cinema', $strategy);

        self::assertCount(1, $strategy->asked);
        self::assertSame('thuecat:Cinema', $strategy->asked[0]['class']);
        self::assertSame(
            ['schema:EntertainmentBusiness', 'schema:EventVenue'],
            $strategy->asked[0]['candidates']
        );
    }

    #[Test]
    public function followsOnlyTheChosenBranch(): void
    {
        $chain = $this->build([
            'thuecat:Cinema' => ['schema:EntertainmentBusiness', 'schema:EventVenue'],
            'schema:EntertainmentBusiness' => [],
            'schema:EventVenue' => [],
        ], 'thuecat:Cinema');

        self::assertSame(['schema:EntertainmentBusiness', 'thuecat:Cinema'], $chain);
        self::assertNotContains('schema:EventVenue', $chain, 'One record, one parent.');
    }

    #[Test]
    public function yieldsTheSameChainOnEveryBuild(): void
    {
        $parents = [
            'thuecat:Cinema' => ['schema:EntertainmentBusiness', 'schema:EventVenue'],
            'schema:EntertainmentBusiness' => [],
            'schema:EventVenue' => [],
        ];

        self::assertSame(
            $this->build($parents, 'thuecat:Cinema'),
            $this->build($parents, 'thuecat:Cinema'),
            'A re-import must not re-parent for want of a stable choice.'
        );
    }

    #[Test]
    public function eachClassAppearsOnceWhenBranchesReconverge(): void
    {
        $chain = $this->build([
            'thuecat:Split' => ['schema:Left', 'schema:Right'],
            'schema:Left' => ['schema:Shared'],
            'schema:Right' => ['schema:Shared'],
            'schema:Shared' => [],
        ], 'thuecat:Split');

        self::assertSame(array_values(array_unique($chain)), $chain);
    }
}
