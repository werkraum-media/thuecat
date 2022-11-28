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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Geo;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\OpeningHour;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\ContainedInPlace;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\Organization;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

class Place extends Base
{
    use Organization;
    use ContainedInPlace;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Geo
     */
    protected $geo;

    /**
     * @var OpeningHour[]
     */
    protected $openingHours = [];

    /**
     * @var OpeningHour[]
     */
    protected $specialOpeningHours = [];

    /**
     * @var ForeignReference[]
     */
    protected $parkingFacilitiesNearBy = [];

    /**
     * @var string[]
     */
    protected $sanitations = [];

    /**
     * @var string[]
     */
    protected $otherServices = [];

    /**
     * @var string[]
     */
    protected $trafficInfrastructures = [];

    /**
     * @var string[]
     */
    protected $paymentsAccepted = [];

    /**
     * @var string
     */
    protected $distanceToPublicTransport = '';

    /**
     * @var ForeignReference
     */
    protected $accessibilitySpecification;

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getGeo(): ?Geo
    {
        return $this->geo;
    }

    /**
     * @return ForeignReference[]
     */
    public function getParkingFacilitiesNearBy(): array
    {
        return $this->parkingFacilitiesNearBy;
    }

    /**
     * @return string[]
     */
    public function getSanitations(): array
    {
        return $this->sanitations;
    }

    /**
     * @return string[]
     */
    public function getOtherServices(): array
    {
        return $this->otherServices;
    }

    /**
     * @return string[]
     */
    public function getTrafficInfrastructures(): array
    {
        return $this->trafficInfrastructures;
    }

    /**
     * @return string[]
     */
    public function getPaymentsAccepted(): array
    {
        return $this->paymentsAccepted;
    }

    public function getDistanceToPublicTransport(): string
    {
        return $this->distanceToPublicTransport;
    }

    public function getAccessibilitySpecification(): ?ForeignReference
    {
        return $this->accessibilitySpecification;
    }

    /**
     * @return OpeningHour[]
     */
    public function getOpeningHoursSpecification(): array
    {
        return GeneralUtility::makeInstance(DateBasedFilter::class)
            ->filterOutPreviousDates(
                $this->openingHours,
                function (OpeningHour $hour): ?\DateTimeImmutable {
                    return $hour->getValidThrough();
                }
            );
    }

    /**
     * @return OpeningHour[]
     */
    public function getSpecialOpeningHoursSpecification(): array
    {
        return GeneralUtility::makeInstance(DateBasedFilter::class)
            ->filterOutPreviousDates(
                $this->specialOpeningHours,
                function (OpeningHour $hour): ?\DateTimeImmutable {
                    return $hour->getValidThrough();
                }
            );
    }

    /**
     * @return ForeignReference[]
     */
    public function getParkingFacilityNearBy(): array
    {
        return $this->parkingFacilitiesNearBy;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setGeo(Geo $geo): void
    {
        $this->geo = $geo;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addOpeningHoursSpecification(OpeningHour $openingHour): void
    {
        $this->openingHours[] = $openingHour;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeOpeningHoursSpecification(OpeningHour $openingHour): void
    {
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addSpecialOpeningHoursSpecification(OpeningHour $openingHour): void
    {
        $this->specialOpeningHours[] = $openingHour;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeSpecialOpeningHoursSpecification(OpeningHour $openingHour): void
    {
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addParkingFacilityNearBy(ForeignReference $parkingFacilityNearBy): void
    {
        $this->parkingFacilitiesNearBy[] = $parkingFacilityNearBy;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeParkingFacilityNearBy(ForeignReference $parkingFacilityNearBy): void
    {
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $sanitation
     */
    public function setSanitation($sanitation): void
    {
        if (is_string($sanitation)) {
            $sanitation = [$sanitation];
        }

        $this->sanitations = PropertyValues::removePrefixFromEntries($sanitation);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $otherService
     */
    public function setOtherService($otherService): void
    {
        if (is_string($otherService)) {
            $otherService = [$otherService];
        }

        $this->otherServices = PropertyValues::removePrefixFromEntries($otherService);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $trafficInfrastructure
     */
    public function setTrafficInfrastructure($trafficInfrastructure): void
    {
        if (is_string($trafficInfrastructure)) {
            $trafficInfrastructure = [$trafficInfrastructure];
        }

        $this->trafficInfrastructures = PropertyValues::removePrefixFromEntries($trafficInfrastructure);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $paymentAccepted
     */
    public function setPaymentAccepted($paymentAccepted): void
    {
        if (is_string($paymentAccepted)) {
            $paymentAccepted = [$paymentAccepted];
        }

        $this->paymentsAccepted = PropertyValues::removePrefixFromEntries($paymentAccepted);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setDistanceToPublicTransport(array $distanceToPublicTransport): void
    {
        $unit = $distanceToPublicTransport['unitCode'] ?? '';
        $value = $distanceToPublicTransport['value'] ?? '';
        if ($unit && $value) {
            $this->distanceToPublicTransport = $value . ':' . PropertyValues::removePrefixFromEntry($unit);
        }
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAccessibilitySpecification(ForeignReference $accessibilitySpecification): void
    {
        $this->accessibilitySpecification = $accessibilitySpecification;
    }
}
