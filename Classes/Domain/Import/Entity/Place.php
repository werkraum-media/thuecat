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

use DateTimeImmutable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Geo;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\OpeningHour;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\ContainedInPlace;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\Organization;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

class Place extends Base
{
    use Organization;
    use ContainedInPlace;

    protected ?Address $address = null;

    protected ?Geo $geo = null;

    /**
     * @var OpeningHour[]
     */
    protected array $openingHoursSpecifications = [];

    /**
     * @var OpeningHour[]
     */
    protected array $specialOpeningHours = [];

    /**
     * @var ForeignReference[]
     */
    protected array $parkingFacilitiesNearBy = [];

    /**
     * @var string[]
     */
    protected array $sanitations = [];

    /**
     * @var string[]
     */
    protected array $otherServices = [];

    /**
     * @var string[]
     */
    protected array $trafficInfrastructures = [];

    /**
     * @var string[]
     */
    protected array $paymentsAccepted = [];

    protected string $distanceToPublicTransport = '';

    protected ?ForeignReference $accessibilitySpecification = null;

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
                $this->openingHoursSpecifications,
                function (OpeningHour $hour): ?DateTimeImmutable {
                    return $hour->getValidThrough();
                }
            )
        ;
    }

    /**
     * @return OpeningHour[]
     */
    public function getSpecialOpeningHoursSpecification(): array
    {
        return GeneralUtility::makeInstance(DateBasedFilter::class)
            ->filterOutPreviousDates(
                $this->specialOpeningHours,
                function (OpeningHour $hour): ?DateTimeImmutable {
                    return $hour->getValidThrough();
                }
            )
        ;
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
        $this->openingHoursSpecifications[] = $openingHour;
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
     *
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
     *
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
     *
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
     *
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

        $types = [];
        $meansOfTransport = $distanceToPublicTransport['meansOfTransport'] ?? [];
        if (is_string($meansOfTransport)) {
            $meansOfTransport = [$meansOfTransport];
        }
        foreach ($meansOfTransport as $type) {
            $types[] = PropertyValues::removePrefixFromEntry($type);
        }

        if ($unit && $value) {
            $this->distanceToPublicTransport = $value . ':' . PropertyValues::removePrefixFromEntry($unit);
            if ($types !== []) {
                $this->distanceToPublicTransport .= ':' . implode(':', $types);
            }
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
