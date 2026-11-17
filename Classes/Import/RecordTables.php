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

use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\MeetingRoomEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\ParkingFacilityEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\PlaceEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristInformationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TownEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailEntity;

/**
 * Tables a ThueCat resource is stored in as a record of its own. Rows stored
 * inline under a parent belong to that parent and are not listed.
 *
 * A new standalone record kind joins this list, or its rows silently go
 * without an origin, never count as a sync channel folder's content and are
 * never deleted when a channel removes them.
 */
final class RecordTables
{
    public const array STANDALONE = [
        EventEntity::TABLE,
        TouristAttractionEntity::TABLE,
        PlaceEntity::TABLE,
        TouristInformationEntity::TABLE,
        ParkingFacilityEntity::TABLE,
        TownEntity::TABLE,
        OrganisationEntity::TABLE,
        MeetingRoomEntity::TABLE,
        TrailEntity::TABLE,
    ];
}
