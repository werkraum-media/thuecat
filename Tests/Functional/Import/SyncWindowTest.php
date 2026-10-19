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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncWindow;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class SyncWindowTest extends AbstractFunctionalTestCase
{
    private const NOW = '2026-10-19 12:00';

    private const FIVE_YEARS_BEFORE_NOW = '2021-10-19 12:00';

    /**
     * Configuration uids and storage pids refer to Fixtures/Import/SyncWindow.csv.
     *
     * @return iterable<string, array{int, int, string}>
     */
    public static function windows(): iterable
    {
        yield 'the last run succeeded' => [1, 10, '2026-10-14 03:00'];
        yield 'a runAborted run does not move the anchor' => [2, 10, '2026-10-14 03:00'];
        yield 'a runFailed run does not move the anchor' => [3, 10, '2026-10-14 03:00'];
        yield 'a fetchingError run does not move the anchor' => [4, 10, '2026-10-14 03:00'];
        yield 'a skipped reference and a mapping error do not disqualify a run' => [5, 10, '2026-10-14 03:00'];
        yield 'another configuration\'s log is ignored' => [6, 10, '2026-10-11 03:00'];
        yield 'content but no qualifying log' => [8, 10, self::FIVE_YEARS_BEFORE_NOW];
        yield 'an empty storage folder' => [1, 11, self::FIVE_YEARS_BEFORE_NOW];
        yield 'a storage folder holding only deleted events' => [1, 12, self::FIVE_YEARS_BEFORE_NOW];
    }

    #[Test]
    #[DataProvider('windows')]
    public function startsTheWindow(int $configurationUid, int $storagePid, string $expectedSince): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncWindow.csv');
        $zone = new DateTimeZone('Europe/Berlin');
        $subject = new SyncWindow(
            $this->get(ImportLogRepository::class),
            $this->get(ConnectionPool::class),
            $this->get(TcaSchemaFactory::class),
            new FixedClock(new DateTimeImmutable(self::NOW, $zone)),
        );

        self::assertEquals(
            new DateTimeImmutable($expectedSince, $zone),
            $subject->since($configurationUid, $storagePid)
        );
    }
}
