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

use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;

class TouristAttraction extends Place implements MapsToType
{
    /**
     * @var string[]
     */
    protected $slogan = [];

    /**
     * @var string
     */
    protected $startOfConstruction = '';

    /**
     * @var string[]
     */
    protected $museumServices = [];

    /**
     * @var string[]
     */
    protected $architecturalStyles = [];

    /**
     * @var string[]
     */
    protected $digitalOffers = [];

    /**
     * @var string[]
     */
    protected $photographies = [];

    /**
     * @var string
     */
    protected $petsAllowed = '';

    /**
     * @var string
     */
    protected $isAccessibleForFree = '';

    /**
     * @var string
     */
    protected $publicAccess = '';

    /**
     * @var string[]
     */
    protected $availableLanguages = [];

    public function getSlogan(): array
    {
        return $this->slogan;
    }

    public function getStartOfConstruction(): string
    {
        return $this->startOfConstruction;
    }

    /**
     * @return string[]
     */
    public function getMuseumServices(): array
    {
        return $this->museumServices;
    }

    /**
     * @return string[]
     */
    public function getArchitecturalStyles(): array
    {
        return $this->architecturalStyles;
    }

    /**
     * @return string[]
     */
    public function getDigitalOffers(): array
    {
        return $this->digitalOffers;
    }

    /**
     * @return string[]
     */
    public function getPhotographies(): array
    {
        return $this->photographies;
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

    /**
     * @return string[]
     */
    public function getAvailableLanguages(): array
    {
        return $this->availableLanguages;
    }

    /**
     * @internal for mapping via Symfony component.
     *
     * @param string|array $slogan
     */
    public function setSlogan($slogan): void
    {
        if (is_string($slogan)) {
            $slogan = [$slogan];
        }
        $this->slogan = PropertyValues::removePrefixFromEntries($slogan);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setStartOfConstruction(string $startOfConstruction): void
    {
        $this->startOfConstruction = $startOfConstruction;
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $museumService
     */
    public function setMuseumService($museumService): void
    {
        if (is_string($museumService)) {
            $museumService = [$museumService];
        }

        $this->museumServices = PropertyValues::removePrefixFromEntries($museumService);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $architecturalStyle
     */
    public function setArchitecturalStyle($architecturalStyle): void
    {
        if (is_string($architecturalStyle)) {
            $architecturalStyle = [$architecturalStyle];
        }

        $this->architecturalStyles = PropertyValues::removePrefixFromEntries($architecturalStyle);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $digitalOffer
     */
    public function setDigitalOffer($digitalOffer): void
    {
        if (is_string($digitalOffer)) {
            $digitalOffer = [$digitalOffer];
        }

        $this->digitalOffers = PropertyValues::removePrefixFromEntries($digitalOffer);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $photography
     */
    public function setPhotography($photography): void
    {
        if (is_string($photography)) {
            $photography = [$photography];
        }

        $this->photographies = PropertyValues::removePrefixFromEntries($photography);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPetsAllowed(string $petsAllowed): void
    {
        $this->petsAllowed = $petsAllowed;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setIsAccessibleForFree(string $isAccessibleForFree): void
    {
        $this->isAccessibleForFree = $isAccessibleForFree;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPublicAccess(string $publicAccess): void
    {
        $this->publicAccess = $publicAccess;
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $availableLanguage
     */
    public function setAvailableLanguage($availableLanguage): void
    {
        if (is_string($availableLanguage)) {
            $availableLanguage = [$availableLanguage];
        }

        $this->availableLanguages = PropertyValues::removePrefixFromEntries($availableLanguage);
    }

    public static function getSupportedTypes(): array
    {
        return [
            'schema:TouristAttraction',
        ];
    }
}
