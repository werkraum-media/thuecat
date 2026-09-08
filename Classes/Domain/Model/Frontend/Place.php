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
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\MergedByWeekday;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\PerDayTable;
use WerkraumMedia\ThueCat\Service\OpeningHoursFormatter;

abstract class Place extends Base
{
    /**
     * @var ObjectStorage<Address>
     */
    protected ObjectStorage $addressInline;

    protected string $url = '';

    /**
     * Imported tx_thuecat_opening_hours child rows (regular). Mapped for the
     * formatter; never rendered bare — use getPerDayTable().
     *
     * @var ObjectStorage<OpeningHourSpecification>
     */
    protected ObjectStorage $openingHoursInline;

    /**
     * @var ObjectStorage<OpeningHourSpecification>
     */
    protected ObjectStorage $specialOpeningHoursInline;

    /**
     * @var ObjectStorage<ParkingFacility>
     */
    protected ObjectStorage $parkingFacilityNearBy;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $editorialImages;

    /**
     * @var string
     */
    protected string $sanitation = '';

    protected string $otherService = '';

    protected string $trafficInfrastructure = '';

    /**
     * @var string
     */
    protected string $paymentAccepted = '';

    /**
     * @var string
     */
    protected string $distanceToPublicTransport = '';

    protected ?AccessiblitySpecification $accessibilitySpecification = null;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->parkingFacilityNearBy = new ObjectStorage();
        $this->openingHoursInline = new ObjectStorage();
        $this->specialOpeningHoursInline = new ObjectStorage();
        $this->addressInline = new ObjectStorage();
        $this->editorialImages = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function getAddressInline(): ObjectStorage
    {
        return $this->addressInline;
    }

    /** The first address, for the common single-address case. */
    public function getFirstAddress(): ?Address
    {
        foreach ($this->addressInline as $address) {
            return $address;
        }

        return null;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getEditorialImages(): ObjectStorage
    {
        return $this->editorialImages;
    }

    /**
     * Regular opening hours in the per-day-table format computed from the
     * imported inline records: grouped into periods, weekdays Monday-first, each
     * day carrying all its relevant time periods, open-now resolved.
     */
    public function getPerDayTable(): PerDayTable
    {
        return GeneralUtility::makeInstance(OpeningHoursFormatter::class)
            ->buildPerDayTable($this->openingHoursInline)
        ;
    }

    /**
     * Special/deviating opening hours (Feiertage etc.) in the per-day-table
     * format, same shape as getPerDayTable().
     */
    public function getSpecialPerDayTable(): PerDayTable
    {
        return GeneralUtility::makeInstance(OpeningHoursFormatter::class)
            ->buildPerDayTable($this->specialOpeningHoursInline)
        ;
    }

    /**
     * Regular opening hours in the merged-by-weekday format: weekdays sharing
     * identical hours collapsed into one group (e.g. Monday–Friday),
     * PublicHolidays kept separate.
     */
    public function getMergedByWeekday(): MergedByWeekday
    {
        return GeneralUtility::makeInstance(OpeningHoursFormatter::class)
            ->buildMergedByWeekday($this->openingHoursInline)
        ;
    }

    /**
     * Special/deviating opening hours in the merged-by-weekday format, same
     * shape as getMergedByWeekday().
     */
    public function getSpecialMergedByWeekday(): MergedByWeekday
    {
        return GeneralUtility::makeInstance(OpeningHoursFormatter::class)
            ->buildMergedByWeekday($this->specialOpeningHoursInline)
        ;
    }

    /**
     * @return ObjectStorage<OpeningHourSpecification>
     */
    public function getOpeningHoursInline(): ObjectStorage
    {
        return $this->openingHoursInline;
    }

    /**
     * @return ObjectStorage<OpeningHourSpecification>
     */
    public function getSpecialOpeningHoursInline(): ObjectStorage
    {
        return $this->specialOpeningHoursInline;
    }

    public function getParkingFacilityNearBy(): ObjectStorage
    {
        return $this->parkingFacilityNearBy;
    }

    public function setParkingFacilityNearBy(ObjectStorage $parkingFacilityNearBy): void
    {
        $this->parkingFacilityNearBy = $parkingFacilityNearBy;
    }

    public function getParkingFacilitiesNearBy(): ObjectStorage
    {
        return $this->parkingFacilityNearBy;
    }

    /**
     * @return ParkingFacility[]
     */
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
            'value' => $values[0],
            'unit' => $values[1] ?? '',
            'types' => GeneralUtility::trimExplode(':', $values[2] ?? '', true),
        ];
    }

    public function getAccessibilitySpecification(): ?AccessiblitySpecification
    {
        return $this->accessibilitySpecification;
    }
}
