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

class ParkingFacility extends AbstractContainedPlace
{
    protected int $spacesCar = 0;

    protected int $spacesHandicapped = 0;

    protected int $spacesBus = 0;

    protected int $spacesEvCharging = 0;

    protected int $spacesMotorcycle = 0;

    protected ?Offers $offers = null;

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
}
