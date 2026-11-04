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

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A room of a meeting venue. It has no page of its own; its venue renders it. The related rooms are stored as
 * upstream delivers them, without telling a combined room from its parts.
 */
class MeetingRoom extends Base
{
    protected float $floorSize = 0.0;

    protected float $roomHeight = 0.0;

    protected int $capacityBanquet = 0;

    protected int $capacityClassroom = 0;

    protected int $capacityTheatre = 0;

    protected int $capacityStanding = 0;

    protected string $facilities = '';

    protected int $numberOfRooms = 0;

    protected string $amenityFeatures = '';

    protected string $bookable = '';

    protected string $url = '';

    protected string $tourBookingPage = '';

    protected ?FileReference $floorPlan = null;

    /**
     * @var ObjectStorage<MeetingRoom>
     */
    protected ObjectStorage $containedRooms;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->containedRooms = new ObjectStorage();
    }

    public function getFloorSize(): float
    {
        return $this->floorSize;
    }

    public function getRoomHeight(): float
    {
        return $this->roomHeight;
    }

    public function getCapacityBanquet(): int
    {
        return $this->capacityBanquet;
    }

    public function getCapacityClassroom(): int
    {
        return $this->capacityClassroom;
    }

    public function getCapacityTheatre(): int
    {
        return $this->capacityTheatre;
    }

    public function getCapacityStanding(): int
    {
        return $this->capacityStanding;
    }

    /**
     * @return list<string>
     */
    public function getFacilityMembers(): array
    {
        return array_values(array_filter(explode(',', $this->facilities)));
    }

    public function getNumberOfRooms(): int
    {
        return $this->numberOfRooms;
    }

    /**
     * @return list<string>
     */
    public function getAmenityFeatureMembers(): array
    {
        return array_values(array_filter(explode(',', $this->amenityFeatures)));
    }

    /**
     * 'true', 'false', or empty when upstream does not say.
     */
    public function getBookable(): string
    {
        return $this->bookable;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTourBookingPage(): string
    {
        return $this->tourBookingPage;
    }

    public function getFloorPlan(): ?FileReference
    {
        return $this->floorPlan;
    }

    /**
     * @return ObjectStorage<MeetingRoom>
     */
    public function getContainedRooms(): ObjectStorage
    {
        return $this->containedRooms;
    }
}
