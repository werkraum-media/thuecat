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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class MeetingVenue extends TouristAttraction
{
    protected string $meetingFacilities = '';

    protected string $gastro = '';

    protected string $servesCuisine = '';

    protected string $entrance = '';

    protected string $guidedTour = '';

    protected string $monument = '';

    protected string $ensemble = '';

    protected string $exhibitionObjects = '';

    protected string $sportsEquipment = '';

    protected string $amenityFeatures = '';

    protected int $distanceAirport = 0;

    protected int $distanceCityCenter = 0;

    protected int $distanceFair = 0;

    protected int $distanceFreeway = 0;

    protected int $distanceIceStation = 0;

    /**
     * @var ObjectStorage<MeetingRoom>
     */
    protected ObjectStorage $meetingRooms;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->meetingRooms = new ObjectStorage();
    }

    /**
     * @return list<string>
     */
    public function getMeetingFacilities(): array
    {
        return $this->membersOf($this->meetingFacilities);
    }

    /**
     * @return list<string>
     */
    public function getGastro(): array
    {
        return $this->membersOf($this->gastro);
    }

    /**
     * @return list<string>
     */
    public function getServesCuisine(): array
    {
        return $this->membersOf($this->servesCuisine);
    }

    /**
     * @return list<string>
     */
    public function getEntrance(): array
    {
        return $this->membersOf($this->entrance);
    }

    /**
     * @return list<string>
     */
    public function getGuidedTour(): array
    {
        return $this->membersOf($this->guidedTour);
    }

    /**
     * @return list<string>
     */
    public function getMonument(): array
    {
        return $this->membersOf($this->monument);
    }

    /**
     * @return list<string>
     */
    public function getEnsemble(): array
    {
        return $this->membersOf($this->ensemble);
    }

    /**
     * @return list<string>
     */
    public function getExhibitionObjects(): array
    {
        return $this->membersOf($this->exhibitionObjects);
    }

    /**
     * @return list<string>
     */
    public function getSportsEquipment(): array
    {
        return $this->membersOf($this->sportsEquipment);
    }

    /**
     * @return list<string>
     */
    public function getAmenityFeatures(): array
    {
        return $this->membersOf($this->amenityFeatures);
    }

    /**
     * Delivered distances only, keyed by destination.
     *
     * @return array<string, array{value: float|int, unit: 'km'|'m'}>
     */
    public function getDistances(): array
    {
        $distances = [];
        foreach ([
            'airport' => $this->distanceAirport,
            'cityCenter' => $this->distanceCityCenter,
            'fair' => $this->distanceFair,
            'freeway' => $this->distanceFreeway,
            'iceStation' => $this->distanceIceStation,
        ] as $destination => $metres) {
            if ($metres === 0) {
                continue;
            }
            $distances[$destination] = $metres >= 1000
                ? ['value' => $metres / 1000.0, 'unit' => 'km']
                : ['value' => $metres, 'unit' => 'm'];
        }

        return $distances;
    }

    /**
     * @return ObjectStorage<MeetingRoom>
     */
    public function getMeetingRooms(): ObjectStorage
    {
        return $this->meetingRooms;
    }

    /**
     * @return list<MeetingRoom>
     */
    public function getMeetingRoomTable(): array
    {
        // Rooms the venue names keep their venue position, so they count as seen before the walk starts.
        $seen = [];
        foreach ($this->meetingRooms as $room) {
            $seen[$room->getUid()] = true;
        }

        $table = [];
        foreach ($this->meetingRooms as $room) {
            $table[] = $room;
            $this->appendRoomsReachedOnlyThrough($room, $table, $seen);
        }

        return $table;
    }

    /**
     * @param list<MeetingRoom> $table
     * @param array<int|null, true> $seen
     */
    protected function appendRoomsReachedOnlyThrough(MeetingRoom $room, array &$table, array &$seen): void
    {
        foreach ($room->getContainedRooms() as $containedRoom) {
            if (isset($seen[$containedRoom->getUid()])) {
                continue;
            }
            $seen[$containedRoom->getUid()] = true;
            $table[] = $containedRoom;
            $this->appendRoomsReachedOnlyThrough($containedRoom, $table, $seen);
        }
    }

    /**
     * @return list<string>
     */
    protected function membersOf(string $commaList): array
    {
        return array_values(array_filter(explode(',', $commaList)));
    }
}
