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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use WerkraumMedia\ThueCat\Removal\Handler\RemovalFinishedHandler;
use WerkraumMedia\ThueCat\Removal\Handler\RemovalStartedHandler;
use WerkraumMedia\ThueCat\Removal\Handler\RemoveRootHandler;
use WerkraumMedia\ThueCat\Removal\Message\RemovalFinished;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\RootRemoval;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;

final class RemovalChainTest extends AbstractRemovalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
    }

    #[Test]
    public function theStartSetsTheJobRunningAndQueuesTheFirstRoot(): void
    {
        $job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10));

        $this->get(RemovalStartedHandler::class)(new RemovalStarted($job));

        $this->assertSame('running', $this->jobRow($job)['status']);
        $this->assertEquals([new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 1)], $this->queuedMessages());
    }

    #[Test]
    public function theStartOfAnEmptySelectionQueuesTheFinish(): void
    {
        $job = $this->queueJob(Selection::ofKind('tx_events_domain_model_event', Scope::Folder, 4010));

        $this->get(RemovalStartedHandler::class)(new RemovalStarted($job));

        $this->assertEquals([new RemovalFinished($job)], $this->queuedMessages());
    }

    #[Test]
    public function aRootQueuesTheNextRootAndIsRemoved(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 1);

        $this->assertEquals([new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 2)], $this->queuedMessages());
        $this->assertTrue($this->isDeleted('tx_thuecat_tourist_attraction', 1));
        $this->assertEquals(1, $this->jobRow($job)['removed_roots']);
        $this->assertEquals(0, $this->jobRow($job)['skipped_roots']);
    }

    #[Test]
    public function theLastRootQueuesTheFinish(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 2);

        $this->assertEquals([new RemovalFinished($job)], $this->queuedMessages());
    }

    #[Test]
    public function aRootAlreadyGoneIsSkippedAndTheChainGoesOn(): void
    {
        $job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Site, 1));

        $this->get(RemoveRootHandler::class)(new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 6));

        $this->assertEquals([new RemovalFinished($job)], $this->queuedMessages());
        $this->assertEquals(0, $this->jobRow($job)['removed_roots']);
        $this->assertEquals(1, $this->jobRow($job)['skipped_roots']);
    }

    #[Test]
    public function aFailingRootHasAlreadyQueuedTheNextRoot(): void
    {
        $job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10));
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(RootRemoval::class, new RootRemovalThrowingStub());

        $thrown = null;
        try {
            $this->get(RemoveRootHandler::class)(new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 1));
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $this->assertSame(1795085211, $thrown?->getCode());

        $this->assertEquals([new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 2)], $this->queuedMessages());
    }

    #[Test]
    public function aRootIsRemovedWithTheRightsOfTheUserWhoStartedTheJob(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Editor.csv');
        $job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Site, 1), 2);

        $this->get(RemoveRootHandler::class)(new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 1));
        $this->get(RemoveRootHandler::class)(new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 4));

        $this->assertTrue($this->isDeleted('tx_thuecat_tourist_attraction', 1));
        $this->assertFalse($this->isDeleted('tx_thuecat_tourist_attraction', 4));
        $this->assertEquals(1, $this->jobRow($job)['removed_roots']);
        $this->assertEquals(1, $this->jobRow($job)['errors']);
        $errorLog = $this->jobRow($job)['error_log'];
        $this->assertIsString($errorLog);
        $this->assertStringContainsString('tx_thuecat_tourist_attraction:4', $errorLog);
        $this->assertEquals(
            [new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 2), new RemovalFinished($job)],
            $this->queuedMessages()
        );
    }

    #[Test]
    public function theFinishSetsTheJobDone(): void
    {
        $job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10));

        $this->get(RemovalFinishedHandler::class)(new RemovalFinished($job));

        $this->assertSame('done', $this->jobRow($job)['status']);
    }

    #[Test]
    public function messagesOfAnUnknownJobDoNothing(): void
    {
        $this->get(RemovalStartedHandler::class)(new RemovalStarted(999));
        $this->get(RemoveRootHandler::class)(new RemoveRoot(999, 'tx_thuecat_tourist_attraction', 1));

        $this->assertSame([], $this->queuedMessages());
        $this->assertFalse($this->isDeleted('tx_thuecat_tourist_attraction', 1));
    }
}
