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
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\RemovalJob;
use WerkraumMedia\ThueCat\Removal\RemovalJobs;
use WerkraumMedia\ThueCat\Removal\RemovalSelection;
use WerkraumMedia\ThueCat\Removal\RootRecord;
use WerkraumMedia\ThueCat\Removal\RootRemoval;

#[AsMessageHandler]
final readonly class RemoveRootHandler
{
    public function __construct(
        private RemovalJobs $removalJobs,
        private RemovalSelection $removalSelection,
        private MessageBusInterface $messageBus,
        private RootRemoval $rootRemoval,
    ) {
    }

    public function __invoke(RemoveRoot $message): void
    {
        $job = $this->removalJobs->find($message->jobUid);
        if (!$job instanceof RemovalJob) {
            return;
        }

        // The successor is queued first, so whatever happens to this root, the chain goes on.
        $root = new RootRecord($message->table, $message->uid);
        $next = $this->removalSelection->next($job->selection, $root);
        $this->messageBus->dispatch(
            $next instanceof RootRecord ? new RemoveRoot($job->uid, $next->table, $next->uid) : new RemovalFinished($job->uid)
        );

        $this->removalJobs->record($job->uid, $root, $this->rootRemoval->remove($job, $root));
    }
}
