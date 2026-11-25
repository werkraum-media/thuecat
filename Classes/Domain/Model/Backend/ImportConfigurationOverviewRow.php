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
 * One configuration as the imports module lists it.
 */
final readonly class ImportConfigurationOverviewRow
{
    /**
     * @param int $openJobUid 0 without an open job
     * @param string $openJobStatus '' without an open job
     * @param list<ImportJobOverviewRow> $jobs newest first
     */
    public function __construct(
        public int $uid,
        public string $title,
        public string $type,
        public int $storagePid,
        public string $storageTitle,
        public bool $skipMedia,
        public int $scheduleInterval,
        public int $nextRun,
        public int $lastRun,
        public int $openJobUid,
        public string $openJobStatus,
        public array $jobs,
    ) {
    }
}
