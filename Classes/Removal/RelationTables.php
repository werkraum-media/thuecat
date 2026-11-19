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
 * The tables whose records a removal deletes once no remaining record uses them.
 * Roots and sys_category are deliberately absent.
 */
final class RelationTables
{
    public const array ALL = [
        'tx_thuecat_organisation',
        'tx_thuecat_town',
        'tx_thuecat_tourist_information',
        'tx_thuecat_parking_facility',
        'tx_thuecat_meeting_room',
        'tx_events_domain_model_organizer',
        'tx_events_domain_model_location',
    ];
}
