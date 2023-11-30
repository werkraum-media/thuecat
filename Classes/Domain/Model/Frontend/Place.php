<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

abstract class Place extends Base
{
    /**
     * @var Address|null
     */
    protected $address;

    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var OpeningHours|null
     */
    protected $openingHours;

    /**
     * @var OpeningHours|null
     */
    protected $specialOpeningHours;

    /**
     * @var ObjectStorage<ParkingFacility>
     */
    protected $parkingFacilityNearBy;

    /**
     * @var string
     */
    protected $sanitation = '';

    /**
     * @var string
     */
    protected $otherService = '';

    /**
     * @var string
     */
    protected $trafficInfrastructure = '';

    /**
     * @var string
     */
    protected $paymentAccepted = '';

    /**
     * @var string
     */
    protected $distanceToPublicTransport = '';

    /**
     * @var AccessiblitySpecification|null
     */
    protected $accessibilitySpecification;

    public function initializeObject(): void
    {
        $this->parkingFacilityNearBy = new ObjectStorage();
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getOpeningHours(): ?OpeningHours
    {
        return $this->openingHours;
    }

    public function getMergedOpeningHours(): ?MergedOpeningHours
    {
        if ($this->openingHours === null) {
            return null;
        }
        return $this->openingHours->getMerged();
    }

    public function getSpecialOpeningHours(): ?OpeningHours
    {
        return $this->specialOpeningHours;
    }

    public function getMergedSpecialOpeningHours(): ?MergedOpeningHours
    {
        if ($this->specialOpeningHours === null) {
            return null;
        }
        return $this->specialOpeningHours->getMerged();
    }

    public function getParkingFacilitiesNearBy(): ObjectStorage
    {
        return $this->parkingFacilityNearBy;
    }

    public function getParkingFacilitiesNearBySortedByAlphabet(): array
    {
        $facilities = $this->parkingFacilityNearBy->toArray();
        usort($facilities, function (ParkingFacility $a, ParkingFacility $b) {
            return $a->getTitle() <=> $b->getTitle();
        });

        return $facilities;
    }

    public function getSanitation(): array
    {
        return GeneralUtility::trimExplode(',', $this->sanitation, true);
    }

    public function getOtherServices(): array
    {
        return GeneralUtility::trimExplode(',', $this->otherService, true);
    }

    public function getTrafficInfrastructures(): array
    {
        return GeneralUtility::trimExplode(',', $this->trafficInfrastructure, true);
    }

    public function getPaymentAccepted(): array
    {
        return GeneralUtility::trimExplode(',', $this->paymentAccepted, true);
    }

    public function getDistanceToPublicTransport(): array
    {
        $values = GeneralUtility::trimExplode(':', $this->distanceToPublicTransport, true, 3);
        if ($values === []) {
            return [];
        }
        return [
            'value' => $values[0] ?? '',
            'unit' => $values[1] ?? '',
            'types' => GeneralUtility::trimExplode(':', $values[2] ?? '', true),
        ];
    }

    public function getAccessibilitySpecification(): ?AccessiblitySpecification
    {
        return $this->accessibilitySpecification;
    }
}
