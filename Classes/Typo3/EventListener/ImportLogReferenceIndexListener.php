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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;

/**
 * Keeps the import logs out of the reference index. They reference only import records, so they never keep a
 * record in use, yet they would make up a large share of the index and of every full update.
 */
#[AsEventListener]
final readonly class ImportLogReferenceIndexListener
{
    public function __invoke(IsTableExcludedFromReferenceIndexEvent $event): void
    {
        if (in_array($event->getTable(), ['tx_thuecat_import_log', 'tx_thuecat_import_log_entry'], true)) {
            $event->markAsExcluded();
        }
    }
}
