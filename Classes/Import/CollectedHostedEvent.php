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

namespace WerkraumMedia\ThueCat\Import;

/**
 * A stored event that a place names itself, held until the flush relates it. The place is identified by its
 * remote_id, because it may still carry a NEW key while the event already has its uid.
 */
final readonly class CollectedHostedEvent
{
    public function __construct(
        public string $placeRemoteId,
        public string $placeTable,
        public int $eventUid,
    ) {
    }
}
