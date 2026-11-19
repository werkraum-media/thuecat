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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use WerkraumMedia\ThueCat\Removal\Message\RemovalFinished;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;

final class FailedRemovalMessageTest extends AbstractRemovalTestCase
{
    private int $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Editor.csv');
        $this->job = $this->queueJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10), 2);
    }

    /**
     * @return iterable<string, array{callable(int): object}>
     */
    public static function removalMessages(): iterable
    {
        yield 'the start' => [static fn (int $job): object => new RemovalStarted($job)];
        yield 'a root' => [static fn (int $job): object => new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 1)];
        yield 'the finish' => [static fn (int $job): object => new RemovalFinished($job)];
    }

    /**
     * @return iterable<string, array{callable(int): object, string}>
     */
    public static function loggedRemovalMessages(): iterable
    {
        yield 'the start' => [static fn (int $job): object => new RemovalStarted($job), 'Start failed'];
        yield 'a root' => [
            static fn (int $job): object => new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 1),
            'Root tx_thuecat_tourist_attraction:1 failed',
        ];
        yield 'the finish' => [static fn (int $job): object => new RemovalFinished($job), 'Finish failed'];
    }

    /**
     * @param callable(int): object $message
     */
    #[DataProvider('loggedRemovalMessages')]
    #[Test]
    public function aFailedMessageIsLoggedOnItsJob(callable $message, string $label): void
    {
        $this->failMessage($message($this->job));

        $job = $this->jobRow($this->job);
        $this->assertSame(1, $job['errors']);
        $this->assertSame($label . ': Database gone [test] (1795092847)' . "\n", $job['error_log']);
    }

    #[Test]
    public function aFailedMessageIsLoggedInTheSystemLogAsAnErrorOfTheJobsUser(): void
    {
        $this->failMessage(new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1));

        $errors = $this->systemLogErrors(2);
        $this->assertCount(1, $errors);
        $this->assertSame(SystemLogType::ERROR, $errors[0]['type']);
        $this->assertSame(SystemLogErrorClassification::SYSTEM_ERROR, $errors[0]['error']);
        $details = $errors[0]['details'];
        $this->assertStringContainsString('Removal job ' . $this->job . ':', $details);
        $this->assertStringContainsString('Database gone [test]', $details);
        $this->assertStringContainsString('1795092847', $details);
    }

    /**
     * @param callable(int): object $message
     */
    #[DataProvider('removalMessages')]
    #[Test]
    public function aFailedMessageIsQueuedAgainAfterAMinuteWhenNoOtherMessageOfItsJobWaits(callable $message): void
    {
        $this->failMessage($message($this->job));

        $this->assertEquals([$message($this->job)], $this->queuedMessages());
        $body = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')
            ->select(['body'], 'sys_messenger_messages')
            ->fetchOne()
        ;
        $this->assertIsString($body);
        $delay = new PhpSerializer()->decode(['body' => $body])->last(DelayStamp::class);
        $this->assertSame(60000, $delay?->getDelay());
    }

    #[Test]
    public function aFailedMessageIsQueuedAgainWhileOnlyAnotherJobsMessageWaits(): void
    {
        $otherJob = $this->queueJob(Selection::ofKind('tx_thuecat_trail', Scope::Instance));
        $this->get(MessageBusInterface::class)->dispatch(new RemoveRoot($otherJob, 'tx_thuecat_trail', 1));

        $this->failMessage(new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1));

        $this->assertEquals([
            new RemoveRoot($otherJob, 'tx_thuecat_trail', 1),
            new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1),
        ], $this->queuedMessages());
    }

    #[Test]
    public function aFailedMessageIsQueuedAgainWhileOnlyAnUndecodableMessageWaits(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages');
        $connection->insert('sys_messenger_messages', [
            'body' => 'not a message [test]',
            'headers' => '[]',
            'queue_name' => 'default',
            'created_at' => date('Y-m-d H:i:s'),
            'available_at' => date('Y-m-d H:i:s'),
        ]);
        $undecodable = $connection->lastInsertId();

        $this->failMessage(new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1));

        $connection->delete('sys_messenger_messages', ['id' => $undecodable]);
        $this->assertEquals([new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1)], $this->queuedMessages());
    }

    #[Test]
    public function aFailedMessageIsNotQueuedAgainWhenItsJobsSuccessorWaits(): void
    {
        $this->get(MessageBusInterface::class)->dispatch(new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 2));

        $this->failMessage(new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 1));

        $this->assertEquals([new RemoveRoot($this->job, 'tx_thuecat_tourist_attraction', 2)], $this->queuedMessages());
        $this->assertSame(1, $this->jobRow($this->job)['errors']);
    }

    #[Test]
    public function failuresOfAnUnknownJobAreIgnored(): void
    {
        $this->announceFailure(new RemoveRoot($this->job + 1, 'tx_thuecat_tourist_attraction', 1));

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame([], $this->systemLogErrors(1));
    }

    #[Test]
    public function failuresOfOtherMessagesAreIgnored(): void
    {
        $this->announceFailure(new stdClass());

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame([], $this->systemLogErrors(2));
        $this->assertSame(0, $this->jobRow($this->job)['errors']);
    }
}
