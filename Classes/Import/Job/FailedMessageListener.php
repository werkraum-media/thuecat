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

namespace WerkraumMedia\ThueCat\Import\Job;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGeneralAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Service\JobBackendUser;

/**
 * Logs a failed item message on its job's import log and in the system log. Core configures no retry, so the worker
 * drops the message; it is sent again after a minute until its item has had three attempts, then the item is failed
 * and the job goes on without it. An item sends its successor before it can fail, so the chain never waits on it.
 */
#[AsEventListener]
final readonly class FailedMessageListener
{
    private const int MAX_ATTEMPTS = 3;

    private const int RETRY_DELAY_MILLISECONDS = 60000;

    public function __construct(
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportLogger $importLogger,
        private JobBackendUser $jobBackendUser,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof ImportRoot && !$message instanceof DeleteRoot) {
            return;
        }

        $job = $this->importJobs->find($message->jobUid);
        $item = $this->importJobItems->findPending($message->itemUid);
        if (!$job instanceof ImportJob || !$item instanceof ImportJobItem) {
            return;
        }

        $throwable = $event->getThrowable();
        $cause = $throwable instanceof HandlerFailedException ? $throwable->getPrevious() ?? $throwable : $throwable;
        $failure = sprintf(
            'Attempt %d of %d failed: %s (%d)',
            $item->attempts,
            self::MAX_ATTEMPTS,
            $cause->getMessage(),
            $cause->getCode()
        );

        // Entries the failed message recorded are still staged and go into the log along with its failure.
        $this->importLogger->recordItemFailed($item->target, $failure, $item->attempts);
        $dropped = $item->attempts >= self::MAX_ATTEMPTS;
        if ($dropped) {
            $this->importJobItems->markFailed($item);
            $this->importLogger->recordItemDropped($item->target, $item->attempts);
        }

        $this->importLogger->writeLogInto($job->importLog, [], []);
        $this->jobBackendUser->of($job->backendUser)->writelog(
            SystemLogType::ERROR,
            SystemLogGeneralAction::UNDEFINED,
            SystemLogErrorClassification::SYSTEM_ERROR,
            null,
            'Import job %d, %s: %s',
            [$job->uid, $item->target, $failure]
        );

        if (!$dropped) {
            $this->messageBus->dispatch($message, [new DelayStamp(self::RETRY_DELAY_MILLISECONDS)]);
        }
    }
}
