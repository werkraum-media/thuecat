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

namespace WerkraumMedia\ThueCat\Import\SyncChannel;

/**
 * What one run of a sync channel imports and what it deletes, read from the
 * channel's change and expiry lists.
 */
final readonly class SyncChannelLists
{
    /**
     * @param list<string> $idsToImport Bare channel IDs, in the change list's order.
     * @param list<string> $remoteIdsToDelete Resource URLs, as events carry them in `remote_id`.
     */
    public function __construct(
        public array $idsToImport,
        public array $remoteIdsToDelete,
    ) {
    }
}
