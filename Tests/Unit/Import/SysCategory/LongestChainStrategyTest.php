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
use WerkraumMedia\ThueCat\Import\SysCategory\LongestChainStrategy;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

class LongestChainStrategyTest extends TestCase
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
    public function followsTheParentStandingDeepest(): void
    {
        $index = $this->index([
            'schema:Shallow' => [],
            'schema:Deep' => ['schema:Middle'],
            'schema:Middle' => ['schema:Top'],
            'schema:Top' => [],
        ]);

        self::assertSame(
            'schema:Deep',
            (new LongestChainStrategy())->choose($index, 'thuecat:X', ['schema:Shallow', 'schema:Deep'])
        );
    }

    #[Test]
    public function prefersTheDeeperParentWhateverItsPosition(): void
    {
        $index = $this->index([
            'schema:Deep' => ['schema:Middle'],
            'schema:Middle' => [],
            'schema:Shallow' => [],
        ]);

        self::assertSame(
            'schema:Deep',
            (new LongestChainStrategy())->choose($index, 'thuecat:X', ['schema:Deep', 'schema:Shallow'])
        );
    }

    #[Test]
    public function settlesATieByUpstreamOrder(): void
    {
        $index = $this->index([
            'schema:First' => [],
            'schema:Second' => [],
        ]);

        self::assertSame(
            'schema:First',
            (new LongestChainStrategy())->choose($index, 'thuecat:X', ['schema:First', 'schema:Second'])
        );
    }

    #[Test]
    public function choosesTheSameParentOnEveryCall(): void
    {
        $index = $this->index([
            'schema:A' => ['schema:Shared'],
            'schema:B' => ['schema:Shared'],
            'schema:Shared' => [],
        ]);
        $strategy = new LongestChainStrategy();

        self::assertSame(
            $strategy->choose($index, 'thuecat:X', ['schema:A', 'schema:B']),
            $strategy->choose($index, 'thuecat:X', ['schema:A', 'schema:B'])
        );
    }

    #[Test]
    public function namesItselfForTheImportReport(): void
    {
        self::assertSame('longestChain', (new LongestChainStrategy())->name());
    }
}
