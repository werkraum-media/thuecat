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
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\SysCategory\LongestChainStrategy;
use WerkraumMedia\ThueCat\Import\SysCategory\PreferredRootStrategy;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * Candidates below are taken from the published vocabularies: a theatre venue
 * really is both a local business and a tourist attraction, and an event series
 * really is both a series and an event.
 */
class PreferredRootStrategyTest extends TestCase
{
    /** @var list<array{class: string, chosen: string}> */
    private array $warnings = [];

    private function index(): VocabularyIndex
    {
        $parents = [
            'schema:TouristAttraction' => ['schema:Place'],
            'schema:Place' => [],
            'schema:LocalBusiness' => ['schema:Organization'],
            'schema:Organization' => [],
            'schema:Event' => [],
            'schema:Series' => ['schema:Intangible'],
            'schema:Intangible' => [],
            'thuecat:OtherBuilding' => ['schema:TouristAttraction'],
            'schema:CivicStructure' => ['schema:Place'],
            'schema:Book' => [],
        ];

        $classes = [];
        foreach ($parents as $id => $ancestors) {
            $classes[$id] = new VocabularyClass($id, $ancestors, []);
        }

        return new VocabularyIndex($classes);
    }

    private function logger(): ImportLogger
    {
        $logger = self::createStub(ImportLogger::class);
        $logger->method('recordUnpreferredParent')->willReturnCallback(
            function (string $class, string $chosen): void {
                $this->warnings[] = ['class' => $class, 'chosen' => $chosen];
            }
        );

        return $logger;
    }

    /**
     * @param list<string> $roots
     */
    private function strategy(array $roots): PreferredRootStrategy
    {
        return new PreferredRootStrategy('test', $roots, new LongestChainStrategy(), $this->logger());
    }

    #[Test]
    public function followsTheBranchReachingThePreferredRoot(): void
    {
        $chosen = $this->strategy(['schema:Event'])->choose(
            $this->index(),
            'schema:EventSeries',
            ['schema:Series', 'schema:Event']
        );

        self::assertSame('schema:Event', $chosen);
    }

    #[Test]
    public function reachingTheRootIndirectlyCounts(): void
    {
        // OtherBuilding is not TouristAttraction, but descends from it.
        $chosen = $this->strategy(['schema:TouristAttraction'])->choose(
            $this->index(),
            'thuecat:Monument',
            ['thuecat:OtherBuilding', 'schema:CivicStructure']
        );

        self::assertSame('thuecat:OtherBuilding', $chosen);
    }

    #[Test]
    public function takesTheRootsInOrderOfPreference(): void
    {
        // CivicStructure reaches Place; nothing here reaches TouristAttraction.
        $chosen = $this->strategy(['schema:TouristAttraction', 'schema:Place'])->choose(
            $this->index(),
            'schema:LocalBusiness',
            ['schema:Organization', 'schema:CivicStructure']
        );

        self::assertSame('schema:CivicStructure', $chosen);
    }

    #[Test]
    public function prefersTheFirstCandidateReachingTheSameRoot(): void
    {
        $chosen = $this->strategy(['schema:Place'])->choose(
            $this->index(),
            'thuecat:Something',
            ['schema:CivicStructure', 'schema:TouristAttraction']
        );

        self::assertSame('schema:CivicStructure', $chosen, 'Upstream order decides among equals.');
    }

    #[Test]
    public function fallsBackWhenNoCandidateReachesAPreferredRoot(): void
    {
        $chosen = $this->strategy(['schema:Place'])->choose(
            $this->index(),
            'thuecat:Odd',
            ['schema:Book', 'schema:Series']
        );

        // Series is deeper than Book, so the fallback picks it.
        self::assertSame('schema:Series', $chosen);
    }

    #[Test]
    public function warnsWhenItHadToFallBack(): void
    {
        $this->strategy(['schema:Place'])->choose(
            $this->index(),
            'thuecat:Odd',
            ['schema:Book', 'schema:Series']
        );

        // Nobody's rule fits this class, so a person has to look at it.
        self::assertSame([['class' => 'thuecat:Odd', 'chosen' => 'schema:Series']], $this->warnings);
    }

    #[Test]
    public function staysQuietWhenAPreferredRootWasFound(): void
    {
        $this->strategy(['schema:Event'])->choose(
            $this->index(),
            'schema:EventSeries',
            ['schema:Series', 'schema:Event']
        );

        self::assertSame([], $this->warnings);
    }
}
