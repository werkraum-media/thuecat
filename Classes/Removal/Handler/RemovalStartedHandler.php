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

namespace WerkraumMedia\ThueCat\Removal\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use WerkraumMedia\ThueCat\Removal\Message\RemovalFinished;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\ReferenceIndexUpdate;
use WerkraumMedia\ThueCat\Removal\RemovalJob;
use WerkraumMedia\ThueCat\Removal\RemovalJobs;
use WerkraumMedia\ThueCat\Removal\RemovalSelection;
use WerkraumMedia\ThueCat\Removal\RootRecord;

#[AsMessageHandler]
final readonly class RemovalStartedHandler
{
    public function __construct(
        private RemovalJobs $removalJobs,
        private RemovalSelection $removalSelection,
        private MessageBusInterface $messageBus,
        private ReferenceIndexUpdate $referenceIndexUpdate,
    ) {
    }

    public function __invoke(RemovalStarted $message): void
    {
        $job = $this->removalJobs->find($message->jobUid);
        if (!$job instanceof RemovalJob) {
            return;
        }

        $this->removalJobs->markRunning($job->uid);
        if ($job->updateReferenceIndex) {
            $this->referenceIndexUpdate->updateUnlessRecent();
        }

        $first = $this->removalSelection->first($job->selection);
        $this->messageBus->dispatch(
            $first instanceof RootRecord ? new RemoveRoot($job->uid, $first->table, $first->uid) : new RemovalFinished($job->uid)
        );
    }
}
