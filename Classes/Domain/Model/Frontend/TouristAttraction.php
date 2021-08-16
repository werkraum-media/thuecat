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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class TouristAttraction extends AbstractEntity
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $slogan = '';

    /**
     * @var OpeningHours|null
     */
    protected $openingHours = null;

    /**
     * @var Offers|null
     */
    protected $offers = null;

    /**
     * @var Address|null
     */
    protected $address = null;

    /**
     * @var Town|null
     */
    protected $town = null;

    /**
     * @var Media|null
     */
    protected $media = null;

    /**
     * @var string
     */
    protected $startOfConstruction = '';

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
    protected $museumService = '';

    /**
     * @var string
     */
    protected $architecturalStyle = '';

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
    protected $digitalOffer = '';

    /**
     * @var string
     */
    protected $photography = '';

    /**
     * @var string
     */
    protected $petsAllowed = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSlogan(): string
    {
        return $this->slogan;
    }

    public function getOpeningHours(): ?OpeningHours
    {
        return $this->openingHours;
    }

    public function getOffers(): ?Offers
    {
        return $this->offers;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function getStartOfConstruction(): string
    {
        return $this->startOfConstruction;
    }

    public function getSanitation(): array
    {
        return GeneralUtility::trimExplode(',', $this->sanitation, true);
    }

    public function getOtherServices(): array
    {
        return GeneralUtility::trimExplode(',', $this->otherService, true);
    }

    public function getMuseumServices(): array
    {
        return GeneralUtility::trimExplode(',', $this->museumService, true);
    }

    public function getArchitecturalStyles(): array
    {
        return GeneralUtility::trimExplode(',', $this->architecturalStyle, true);
    }

    public function getTrafficInfrastructures(): array
    {
        return GeneralUtility::trimExplode(',', $this->trafficInfrastructure, true);
    }

    public function getPaymentAccepted(): array
    {
        return GeneralUtility::trimExplode(',', $this->paymentAccepted, true);
    }

    public function getDigitalOffer(): array
    {
        return GeneralUtility::trimExplode(',', $this->digitalOffer, true);
    }

    public function getPhotography(): array
    {
        return GeneralUtility::trimExplode(',', $this->photography, true);
    }

    public function getPetsAllowed(): string
    {
        return $this->petsAllowed;
    }
}
