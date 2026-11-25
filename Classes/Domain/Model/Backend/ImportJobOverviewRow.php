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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

/**
 * One job as the imports module lists it beneath its configuration.
 */
final readonly class ImportJobOverviewRow
{
    /**
     * @param int $importLog 0 before the job has started
     * @param int $finishedItems done, failed and skipped items of a running job, 0 otherwise
     * @param int $totalItems all items of a running job, 0 otherwise
     * @param int|null $estimate remaining seconds of a running job, null before its first item is done
     */
    public function __construct(
        public int $uid,
        public string $status,
        public string $trigger,
        public int $backendUser,
        public int $startedAt,
        public int $endedAt,
        public int $doneItems,
        public int $failedItems,
        public int $skippedItems,
        public int $importLog,
        public int $finishedItems,
        public int $totalItems,
        public ?int $estimate,
        public bool $stalled,
    ) {
    }
}
