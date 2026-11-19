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

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * The removal jobs: what was selected, by whom, and how far the removal got.
 *
 * Public, so functional tests can fetch it from the container.
 */
#[Autoconfigure(public: true)]
class RemovalJobs
{
    protected const TABLE = 'tx_thuecat_removal_job';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ClockInterface $clock,
    ) {
    }

    public function queue(Selection $selection, bool $removeFiles, bool $updateReferenceIndex, int $backendUser): int
    {
        $now = $this->clock->now()->getTimestamp();
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->insert(self::TABLE, [
            'crdate' => $now,
            'progressed_at' => $now,
            'backend_user' => $backendUser,
            'status' => 'queued',
            'root_table' => $selection->rootTable,
            'scope' => $selection->scope->value,
            'scope_page' => $selection->scopePage,
            'origin_configuration_uid' => $selection->originConfigurationUid,
            'remove_files' => (int)$removeFiles,
            'update_reference_index' => (int)$updateReferenceIndex,
        ]);

        return (int)$connection->lastInsertId();
    }
}
