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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\Job;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\SteppingClock;

final class ImportRootTest extends AbstractImportTestCase
{
    private const string TRAIL = 'https://thuecat.org/resources/e_106954656-oatour';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
    }

    #[Test]
    public function itemSendsTheNextPendingItemsMessage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/SecondTrailUrl.csv');
        $job = $this->startJob(1);
        $items = $this->jobItems($job);

        $this->handleImportRoot($job, $items[0]['uid']);

        $this->assertEquals([new ImportRoot($job, $items[1]['uid'])], $this->queuedMessages());
    }

    #[Test]
    public function lastItemSendsTheFinishingMessage(): void
    {
        $job = $this->startJob(1);

        $this->handleImportRoot($job, $this->jobItems($job)[0]['uid']);

        $this->assertEquals([new ImportFinished($job)], $this->queuedMessages());
    }

    #[Test]
    public function trailIsStoredWithItsTranslationsAndRelationsWhenItsMessageEnds(): void
    {
        $job = $this->startJob(1);

        $this->handleImportRoot($job, $this->jobItems($job)[0]['uid']);

        $trail = $this->fetchRowByRemoteId('tx_thuecat_trail', self::TRAIL);
        $this->assertSame(
            $this->fetchUidByRemoteId('tx_thuecat_organisation', 'https://thuecat.org/resources/018132452787-ngbe'),
            $trail['managed_by']
        );
        $this->assertIsInt($trail['uid']);
        $this->assertEqualsCanonicalizing([1, 2], $this->fetchColumnMatching('tx_thuecat_trail', 'sys_language_uid', ['l18n_parent' => $trail['uid']]));
    }

    #[Test]
    public function itemIsDoneWithItsDuration(): void
    {
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportJobItems::class, new ImportJobItems(
            $this->get(ConnectionPool::class),
            new SteppingClock(new DateTimeImmutable('@1795526700'), new DateTimeImmutable('@1795526704')),
        ));
        $job = $this->startJob(1);

        $this->handleImportRoot($job, $this->jobItems($job)[0]['uid']);

        $item = $this->jobItems($job)[0];
        $this->assertSame('done', $item['state']);
        $this->assertSame(4, $item['duration']);
    }
}
