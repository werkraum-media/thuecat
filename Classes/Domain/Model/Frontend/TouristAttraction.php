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

class TouristAttraction extends Place
{
    protected string $slogan = '';

    protected ?Offers $offers = null;

    protected ?Town $town = null;

    protected string $startOfConstruction = '';

    protected string $museumService = '';

    protected string $architecturalStyle = '';

    /**
     * Necessary for Extbase/Symfony.
     *
     * @var string
     */
    protected string $digitalOffer = '';

    /**
     * Necessary for Extbase/Symfony.
     *
     * @var string
     */
    protected string $photography = '';

    protected string $petsAllowed = '';

    protected string $isAccessibleForFree = '';

    protected string $publicAccess = '';

    public function getSlogan(): string
    {
        return $this->slogan;
    }

    public function getOffers(): ?Offers
    {
        return $this->offers;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function getStartOfConstruction(): string
    {
        return $this->startOfConstruction;
    }

    public function getMuseumServices(): array
    {
        return GeneralUtility::trimExplode(',', $this->museumService, true);
    }

    public function getArchitecturalStyles(): array
    {
        return GeneralUtility::trimExplode(',', $this->architecturalStyle, true);
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

    public function getIsAccessibleForFree(): string
    {
        return $this->isAccessibleForFree;
    }

    public function getPublicAccess(): string
    {
        return $this->publicAccess;
    }
}
