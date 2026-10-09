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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class BackendOrderTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BasicPages.php');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendOrder.csv');
    }

    #[Test]
    #[DataProvider('tables')]
    public function movingARecordToTheTopGivesItTheLowestSorting(string $table, string $prefix): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->start([], [$table => [2 => ['move' => 10]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $dataHandler->errorLog);

        $unmoved = $this->fetchRowByRemoteId($table, $prefix . '-a');
        $moved = $this->fetchRowByRemoteId($table, $prefix . '-b');
        self::assertArrayHasKey('sorting', $moved, $table . ' has no backend order column.');
        self::assertLessThan($unmoved['sorting'], $moved['sorting']);
    }

    #[Test]
    #[DataProvider('tables')]
    public function movingARecordAfterAnotherGivesItTheHigherSorting(string $table, string $prefix): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->start([], [$table => [1 => ['move' => -2]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $dataHandler->errorLog);

        $moved = $this->fetchRowByRemoteId($table, $prefix . '-a');
        $target = $this->fetchRowByRemoteId($table, $prefix . '-b');
        self::assertGreaterThan($target['sorting'], $moved['sorting']);
    }

    public static function tables(): iterable
    {
        yield 'attraction' => ['tx_thuecat_tourist_attraction', 'test:attraction'];
        yield 'trail' => ['tx_thuecat_trail', 'test:trail'];
    }
}
