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

use PHPUnit\Framework\Attributes\Test;
use stdClass;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\ImportConfigurationGoneException;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Watchdog\MessageDeadline;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class FailedImportMessageTest extends AbstractImportTestCase
{
    private const string FIRST_ATTEMPT = 'https://thuecat.org/resources/test-trail-first-attempt';

    private const string THIRD_ATTEMPT = 'https://thuecat.org/resources/test-trail-third-attempt';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/FailedImportMessage.csv');
    }

    #[Test]
    public function failureIsLoggedOnTheJobsImportLogNamingItsItem(): void
    {
        $this->failMessage(new ImportRoot(1, 1));

        $this->assertSame([self::FIRST_ATTEMPT], $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'remote_id', ['import_log' => 1, 'type' => 'itemFailed']));
        $message = $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'message', ['import_log' => 1, 'type' => 'itemFailed'])[0] ?? '';
        $this->assertIsString($message);
        $this->assertStringContainsString('Database gone [test]', $message);
    }

    // A message that ends abnormally never reaches its own writing of the log.
    #[Test]
    public function entriesRecordedBeforeTheFailureReachTheJobsLog(): void
    {
        $this->get(ImportLogger::class)->recordSkippedReference('tx_thuecat_trail', self::FIRST_ATTEMPT, 'managed_by', 'https://thuecat.org/resources/test-gone', 'Not found [test]');

        $this->failMessage(new ImportRoot(1, 1));

        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_log_entry', ['import_log' => 1, 'type' => 'referenceSkipped']));
    }

    #[Test]
    public function failureNamesThePhaseItsItemReached(): void
    {
        $this->get(MessageDeadline::class)->assertNotExpired(ImportPhase::Persist);

        $this->failMessage(new ImportRoot(1, 1));

        $this->assertSame(['attempt' => 1, 'phase' => 'persist'], $this->logEntryContext('itemFailed'));
    }

    #[Test]
    public function failureOfAnImportItemThatReachedNoPhaseNamesNone(): void
    {
        $this->get(MessageDeadline::class)->assertNotExpired(ImportPhase::Persist);
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_configuration')
            ->delete('tx_thuecat_import_configuration', ['uid' => 1])
        ;
        try {
            $this->handleImportRoot(1, 1);
            $this->fail('The configuration is gone.');
        } catch (ImportConfigurationGoneException) {
        }

        $this->failMessage(new ImportRoot(1, 1));

        $this->assertSame(['attempt' => 2], $this->logEntryContext('itemFailed'));
    }

    #[Test]
    public function failureOfADeleteItemNamesNoPhase(): void
    {
        $this->get(MessageDeadline::class)->assertNotExpired(ImportPhase::Persist);

        $this->failMessage(new DeleteRoot(1, 3));

        $this->assertSame(['attempt' => 1], $this->logEntryContext('itemFailed'));
    }

    #[Test]
    public function failureIsLoggedInTheSystemLogAsAnErrorOfTheJobsUser(): void
    {
        $this->failMessage(new ImportRoot(1, 1));

        $errors = $this->systemLogErrors(1);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Database gone [test]', $errors[0]['details']);
    }

    #[Test]
    public function itemIsSentAgainAfterAMinuteWhileItHasFewerThanThreeAttempts(): void
    {
        $this->failMessage(new ImportRoot(1, 1));

        $this->assertEquals([new ImportRoot(1, 1)], $this->queuedMessages());
        $this->assertSame(60000, $this->queuedMessageDelay());
        $this->assertSame('pending', $this->jobItemStates(1)[0]);
    }

    #[Test]
    public function deleteItemIsSentAgainAsWell(): void
    {
        $this->failMessage(new DeleteRoot(1, 3));

        $this->assertEquals([new DeleteRoot(1, 3)], $this->queuedMessages());
    }

    #[Test]
    public function itemFailingItsThirdAttemptIsDroppedAndNotSentAgain(): void
    {
        $this->failMessage(new ImportRoot(1, 2));

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(['pending', 'failed', 'pending'], $this->jobItemStates(1));
        $this->assertSame([self::THIRD_ATTEMPT], $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'remote_id', ['import_log' => 1, 'type' => 'itemDropped']));
    }

    // The module reads entries through their classes; an entry type without one is invisible there.
    #[Test]
    public function failureAndDropOfAnItemReachTheImportModule(): void
    {
        $this->failMessage(new ImportRoot(1, 2));

        $log = $this->get(ImportLogRepository::class)->findByUid(1);
        $this->assertInstanceOf(ImportLog::class, $log);
        $this->assertSame(
            ['itemFailed', 'itemDropped'],
            array_map(static fn (ImportLogEntry $entry): string => $entry->getType(), $log->getEntries()->toArray())
        );
    }

    #[Test]
    public function failuresOfOtherMessagesAreIgnored(): void
    {
        $this->announceFailure(new stdClass());
        $this->announceFailure(new ImportFinished(1));

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame([], $this->systemLogErrors(1));
        $this->assertSame(0, $this->countRows('tx_thuecat_import_log_entry'));
    }

    #[Test]
    public function failuresOfAnItemOrJobThatIsGoneAreIgnored(): void
    {
        $this->announceFailure(new ImportRoot(99, 1));
        $this->announceFailure(new ImportRoot(1, 99));

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame([], $this->systemLogErrors(1));
        $this->assertSame(0, $this->countRows('tx_thuecat_import_log_entry'));
    }
}
