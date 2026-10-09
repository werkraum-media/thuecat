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

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * What an editor's re-sorting in the backend does to stored lists, with core's
 * cache clearing relied on as it is.
 *
 * Core discards only the pages that showed the moved record. A stored page in
 * backend order that never showed it keeps its old content, so the visitor sees
 * one record twice and another not at all until that page is discarded for
 * another reason. Title-order lists are not affected.
 *
 * These tests assert the observed behaviour, not the desired one.
 *
 * Folder 13 holds Iota (7), Kappa (8) and Lambda (9) in that backend order, two
 * per page. Plugin 60 lists them in backend order, plugin 70 by title; both
 * pages of both plugins are stored before each move.
 */
final class BackendOrderInvalidationTest extends AbstractCachingTestCase
{
    protected const TITLES = ['Iota Insel', 'Kappa Kapelle', 'Lambda Linde'];

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSorting.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
        foreach ([60, 70] as $pageId) {
            $this->request($pageId, ['currentPage' => '1']);
            $this->request($pageId, ['currentPage' => '2']);
        }
    }

    /**
     * @param list<string> $firstPage
     * @param list<string> $secondPage
     */
    #[Test]
    #[DataProvider('moves')]
    public function aBackendOrderListKeepsThePageThatNeverShowedTheMovedRecord(
        int $uid,
        int $target,
        array $firstPage,
        array $secondPage
    ): void {
        $this->moveRecord($uid, $target);

        $first = (string)$this->request(60, ['currentPage' => '1'])->getBody();
        $second = (string)$this->request(60, ['currentPage' => '2'])->getBody();
        foreach (self::TITLES as $title) {
            self::assertSame(in_array($title, $firstPage, true), str_contains($first, $title), 'Page 1, ' . $title);
            self::assertSame(in_array($title, $secondPage, true), str_contains($second, $title), 'Page 2, ' . $title);
        }
    }

    /**
     * @param list<string> $firstPage
     * @param list<string> $secondPage
     */
    #[Test]
    #[DataProvider('moves')]
    public function aTitleOrderListIsUnaffected(
        int $uid,
        int $target,
        array $firstPage,
        array $secondPage
    ): void {
        $this->moveRecord($uid, $target);

        $first = (string)$this->request(70, ['currentPage' => '1'])->getBody();
        $second = (string)$this->request(70, ['currentPage' => '2'])->getBody();
        self::assertStringContainsString('Iota Insel', $first);
        self::assertStringContainsString('Kappa Kapelle', $first);
        self::assertStringContainsString('Lambda Linde', $second);
    }

    /**
     * The correct order would be Lambda, Iota | Kappa and Kappa, Lambda | Iota.
     */
    public static function moves(): iterable
    {
        yield 'to the top of the folder' => [
            'uid' => 9,
            'target' => 13,
            'firstPage' => ['Iota Insel', 'Kappa Kapelle'],
            'secondPage' => ['Kappa Kapelle'],
        ];
        yield 'after another record' => [
            'uid' => 7,
            'target' => -9,
            'firstPage' => ['Kappa Kapelle', 'Lambda Linde'],
            'secondPage' => ['Lambda Linde'],
        ];
    }
}
