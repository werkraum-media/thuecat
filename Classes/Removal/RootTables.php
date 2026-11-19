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

/**
 * The tables whose records an editor removes by kind. Order is the order a
 * selection spanning several kinds walks them in.
 */
final class RootTables
{
    public const array ALL = [
        'tx_thuecat_tourist_attraction',
        'tx_thuecat_place',
        'tx_thuecat_trail',
        'tx_events_domain_model_event',
    ];
}
