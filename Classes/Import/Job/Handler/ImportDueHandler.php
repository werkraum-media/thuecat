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

namespace WerkraumMedia\ThueCat\Import\Job\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;

/**
 * Starts a scheduled job unless an editor moved the next run since the message was queued. An open job refuses the
 * start, and its end schedules the next one.
 */
#[AsMessageHandler]
final readonly class ImportDueHandler
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private ImportJobs $importJobs,
    ) {
    }

    public function __invoke(ImportDue $message): void
    {
        $nextRun = $this->connectionPool->getConnectionForTable('tx_thuecat_import_configuration')
            ->select(['next_run'], 'tx_thuecat_import_configuration', ['uid' => $message->configurationUid])
            ->fetchOne()
        ;
        if (filter_var($nextRun, FILTER_VALIDATE_INT) !== $message->dueAt) {
            return;
        }

        $this->importJobs->queueScheduled($message->configurationUid);
    }
}
