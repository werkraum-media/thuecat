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

class TouristAttraction extends Place
{
    /** Established template variable, shorter than the model name. */
    public const TEMPLATE_VARIABLE_NAME = 'attraction';

    protected string $slogan = '';

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    protected ?Offers $offers = null;

    /**
     * Upstream puts whatever contains a record behind schema:containedInPlace,
     * and a record can belong to more than one town (an airport serving two
     * cities). Both are sets, not single values.
     *
     * @var ObjectStorage<Town>
     */
    protected ObjectStorage $towns;

    /**
     * @var ObjectStorage<Organisation>
     */
    protected ObjectStorage $containedInOrganisation;

    /**
     * containedInPlace gets one relation per target table: Extbase resolves a
     * relation through a single concrete, table-mapped class (its only
     * polymorphism is a recordType column choosing a subclass WITHIN one
     * table), so "any place" cannot be one property. The resolver already
     * knows which table each reference imported into and routes accordingly.
     *
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

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->categories = new ObjectStorage();
        $this->towns = new ObjectStorage();
        $this->containedInOrganisation = new ObjectStorage();
        $this->containedInAttraction = new ObjectStorage();
        $this->containedInTouristInformation = new ObjectStorage();
        $this->containedInParkingFacility = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
    * @return string[]
    */
    public function getSlogans(): array
    {
        return explode(',', $this->slogan);
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
     * Every containing place, whatever table it came from — the template wants
     * one list, the storage is split because Extbase types a relation per table.
     *
     * @return list<Place>
     */
    public function getContainedInPlaces(): array
    {
        return array_merge(
            array_values(iterator_to_array($this->containedInAttraction)),
            array_values(iterator_to_array($this->containedInTouristInformation)),
            array_values(iterator_to_array($this->containedInParkingFacility))
        );
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

    public function getSlogan(): string
    {
        return explode(',', $this->slogan)[0];
    }
}
