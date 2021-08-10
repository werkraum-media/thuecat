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

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours;

class Parser
{
    /**
     * @var GenericFields
     */
    private $genericFields;

    /**
     * @var OpeningHours
     */
    private $openingHours;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var Media
     */
    private $media;

    public function __construct(
        GenericFields $genericFields,
        OpeningHours $openingHours,
        Address $address,
        Media $media
    ) {
        $this->genericFields = $genericFields;
        $this->openingHours = $openingHours;
        $this->address = $address;
        $this->media = $media;
    }

    public function getId(array $jsonLD): string
    {
        return $jsonLD['@id'];
    }

    public function getTitle(array $jsonLD, SiteLanguage $language): string
    {
        return $this->genericFields->getTitle($jsonLD, $language);
    }

    public function getDescription(array $jsonLD, SiteLanguage $language): string
    {
        return $this->genericFields->getDescription($jsonLD, $language);
    }

    public function getManagerId(array $jsonLD): string
    {
        return $jsonLD['thuecat:contentResponsible']['@id'];
    }

    /**
     * @return string[]
     */
    public function getContainedInPlaceIds(array $jsonLD): array
    {
        if (isset($jsonLD['schema:containedInPlace']['@id'])) {
            return [
                $jsonLD['schema:containedInPlace']['@id'],
            ];
        }

        if (isset($jsonLD['schema:containedInPlace']) === false) {
            return [];
        }

        return array_map(function (array $place) {
            return $place['@id'];
        }, $jsonLD['schema:containedInPlace']);
    }

    public function getOpeningHours(array $jsonLD): array
    {
        return $this->openingHours->get($jsonLD);
    }

    public function getAddress(array $jsonLD): array
    {
        return $this->address->get($jsonLD);
    }

    public function getMedia(array $jsonLD): array
    {
        return $this->media->get($jsonLD);
    }
}
