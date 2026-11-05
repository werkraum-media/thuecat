<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

class ParkingFacility extends AbstractPlace
{
    protected int $spacesCar = 0;

    protected int $spacesHandicapped = 0;

    protected int $spacesBus = 0;

    protected int $spacesEvCharging = 0;

    protected int $spacesMotorcycle = 0;

    protected ?Offers $offers = null;

    /**
     * @var ObjectStorage<Town>
     */
    protected ObjectStorage $towns;

    protected ?Organisation $managedBy = null;

    /**
     * @var ObjectStorage<Organisation>
     */
    protected ObjectStorage $containedInOrganisation;

    /**
     * @var ObjectStorage<TouristAttraction>
     */
    protected ObjectStorage $containedInAttraction;

    /**
     * @var ObjectStorage<TouristInformation>
     */
    protected ObjectStorage $containedInTouristInformation;

    /**
     * @var ObjectStorage<ParkingFacility>
     */
    protected ObjectStorage $containedInParkingFacility;

    /**
     * @var ObjectStorage<Place>
     */
    protected ObjectStorage $containedInPlace;

    /**
     * @var ObjectStorage<Trail>
     */
    protected ObjectStorage $containedInTrail;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->towns = new ObjectStorage();
        $this->containedInOrganisation = new ObjectStorage();
        $this->containedInAttraction = new ObjectStorage();
        $this->containedInTouristInformation = new ObjectStorage();
        $this->containedInParkingFacility = new ObjectStorage();
        $this->containedInPlace = new ObjectStorage();
        $this->containedInTrail = new ObjectStorage();
    }

    public function getSpacesCar(): int
    {
        return $this->spacesCar;
    }

    public function getSpacesHandicapped(): int
    {
        return $this->spacesHandicapped;
    }

    public function getSpacesBus(): int
    {
        return $this->spacesBus;
    }

    public function getSpacesEvCharging(): int
    {
        return $this->spacesEvCharging;
    }

    public function getSpacesMotorcycle(): int
    {
        return $this->spacesMotorcycle;
    }

    /**
     * Delivered counts only, keyed by vehicle type. An entry naming several vehicle types counts for each of them,
     * so the values are not summed.
     *
     * @return array<string, int>
     */
    public function getSpaces(): array
    {
        return array_filter([
            'car' => $this->spacesCar,
            'handicapped' => $this->spacesHandicapped,
            'bus' => $this->spacesBus,
            'evCharging' => $this->spacesEvCharging,
            'motorcycle' => $this->spacesMotorcycle,
        ]);
    }

    public function getOffers(): ?Offers
    {
        return $this->offers;
    }

    /**
     * @return ObjectStorage<Town>
     */
    public function getTowns(): ObjectStorage
    {
        return $this->towns;
    }

    public function getManagedBy(): ?Organisation
    {
        return $this->managedBy;
    }

    /**
     * @return ObjectStorage<Organisation>
     */
    public function getContainedInOrganisation(): ObjectStorage
    {
        return $this->containedInOrganisation;
    }

    /**
     * @return ObjectStorage<TouristAttraction>
     */
    public function getContainedInAttraction(): ObjectStorage
    {
        return $this->containedInAttraction;
    }

    /**
     * @return ObjectStorage<TouristInformation>
     */
    public function getContainedInTouristInformation(): ObjectStorage
    {
        return $this->containedInTouristInformation;
    }

    /**
     * @return ObjectStorage<ParkingFacility>
     */
    public function getContainedInParkingFacility(): ObjectStorage
    {
        return $this->containedInParkingFacility;
    }

    /**
     * @return ObjectStorage<Place>
     */
    public function getContainedInPlace(): ObjectStorage
    {
        return $this->containedInPlace;
    }

    /**
     * @return ObjectStorage<Trail>
     */
    public function getContainedInTrail(): ObjectStorage
    {
        return $this->containedInTrail;
    }

    /**
     * Every containing place, whatever table it came from.
     *
     * @return list<AbstractPlace>
     */
    public function getContainedInPlaces(): array
    {
        return array_merge(
            array_values(iterator_to_array($this->containedInAttraction)),
            array_values(iterator_to_array($this->containedInTouristInformation)),
            array_values(iterator_to_array($this->containedInParkingFacility)),
            array_values(iterator_to_array($this->containedInPlace))
        );
    }
}
