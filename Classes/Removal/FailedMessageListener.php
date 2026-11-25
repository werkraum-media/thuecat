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

namespace WerkraumMedia\ThueCat\Removal;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGeneralAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use WerkraumMedia\ThueCat\Removal\Message\RemovalMessage;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Service\JobBackendUser;
use WerkraumMedia\ThueCat\Service\WaitingMessages;

/**
 * Logs a failed removal message on its job and in the system log. Core configures no retry, so the worker drops
 * the message; when no other message of the job waits, that ended the job's chain, and the message is sent again
 * after a minute, so the job resumes by itself once the cause is gone.
 */
#[AsEventListener]
final readonly class FailedMessageListener
{
    private const int RETRY_DELAY_MILLISECONDS = 60000;

    public function __construct(
        private RemovalJobs $removalJobs,
        private JobBackendUser $jobBackendUser,
        private MessageBusInterface $messageBus,
        private WaitingMessages $waitingMessages,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof RemovalMessage) {
            return;
        }

        $job = $this->removalJobs->find($message->jobUid);
        if (!$job instanceof RemovalJob) {
            return;
        }

        $throwable = $event->getThrowable();
        $cause = $throwable instanceof HandlerFailedException ? $throwable->getPrevious() ?? $throwable : $throwable;
        $failure = sprintf('%s failed: %s (%d)', $this->label($message), $cause->getMessage(), $cause->getCode());

        $this->removalJobs->recordFailure($job->uid, $failure);
        $this->jobBackendUser->of($job->backendUser)->writelog(
            SystemLogType::ERROR,
            SystemLogGeneralAction::UNDEFINED,
            SystemLogErrorClassification::SYSTEM_ERROR,
            null,
            'Removal job %d: %s',
            [$job->uid, $failure]
        );

        if (!$this->waitingMessages->exists(static fn (object $waiting): bool => $waiting instanceof RemovalMessage && $waiting->jobUid === $job->uid)) {
            $this->messageBus->dispatch($message, [new DelayStamp(self::RETRY_DELAY_MILLISECONDS)]);
        }
    }

    private function label(RemovalMessage $message): string
    {
        return match (true) {
            $message instanceof RemovalStarted => 'Start',
            $message instanceof RemoveRoot => 'Root ' . $message->table . ':' . $message->uid,
            default => 'Finish',
        };
    }
}
