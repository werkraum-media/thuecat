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

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;

/**
 * The next start is both stored and queued: the stored time lets an editor reschedule and the supervisor recover a
 * lost message, the delayed message starts the job without depending on when cron runs.
 */
#[Autoconfigure(public: true)]
final readonly class ImportSchedule
{
    private const string TABLE = 'tx_thuecat_import_configuration';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ClockInterface $clock,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function scheduleNext(int $configurationUid): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        // A configuration that is gone reads as manual only.
        $schedule = $connection
            ->select(['schedule_interval', 'next_run'], self::TABLE, ['uid' => $configurationUid])
            ->fetchAssociative() ?: [];
        $interval = filter_var($schedule['schedule_interval'] ?? null, FILTER_VALIDATE_INT);
        $nextRun = filter_var($schedule['next_run'] ?? null, FILTER_VALIDATE_INT);
        if ($interval === false || $interval <= 0 || $nextRun === false) {
            return;
        }

        $now = $this->clock->now()->getTimestamp();
        // Whole intervals keep the cadence, such as daily at 03:00, and skip the slots a late job missed.
        if ($nextRun <= $now) {
            $nextRun += (intdiv($now - $nextRun, $interval) + 1) * $interval;
        }

        $connection->update(self::TABLE, ['next_run' => $nextRun], ['uid' => $configurationUid]);
        $this->messageBus->dispatch(
            new ImportDue($configurationUid, $nextRun),
            [new DelayStamp(($nextRun - $now) * 1000)]
        );
    }
}
