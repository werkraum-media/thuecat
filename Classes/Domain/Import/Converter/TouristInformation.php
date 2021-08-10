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

namespace WerkraumMedia\ThueCat\Domain\Import\Converter;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

class TouristInformation implements Converter
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var LanguageHandling
     */
    private $language;

    /**
     * @var OrganisationRepository
     */
    private $organisationRepository;

    /**
     * @var TownRepository
     */
    private $townRepository;

    public function __construct(
        Parser $parser,
        LanguageHandling $language,
        OrganisationRepository $organisationRepository,
        TownRepository $townRepository
    ) {
        $this->parser = $parser;
        $this->language = $language;
        $this->organisationRepository = $organisationRepository;
        $this->townRepository = $townRepository;
    }

    public function convert(array $jsonLD, ImportConfiguration $configuration): EntityCollection
    {
        $language = $this->language->getDefaultLanguage($configuration->getStoragePid());
        $manager = $this->organisationRepository->findOneByRemoteId(
            $this->parser->getManagerId($jsonLD)
        );
        $town = $this->townRepository->findOneByRemoteIds(
            $this->parser->getContainedInPlaceIds($jsonLD)
        );

        $entity = GeneralUtility::makeInstance(
            GenericEntity::class,
            $configuration->getStoragePid(),
            'tx_thuecat_tourist_information',
            0,
            $this->parser->getId($jsonLD),
            [
                'title' => $this->parser->getTitle($jsonLD, $language),
                'description' => $this->parser->getDescription($jsonLD, $language),
                'managed_by' => $manager ? $manager->getUid() : 0,
                'town' => $town ? $town->getUid() : 0,
            ]
        );
        $entities = GeneralUtility::makeInstance(EntityCollection::class);
        $entities->add($entity);

        return $entities;
    }

    public function canConvert(array $type): bool
    {
        return array_search('thuecat:TouristInformation', $type) !== false;
    }
}
