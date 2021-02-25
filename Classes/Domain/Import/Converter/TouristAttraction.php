<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Converter;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Offers;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

class TouristAttraction implements Converter
{
    private Parser $parser;
    private Offers $parserForOffers;
    private LanguageHandling $language;
    private OrganisationRepository $organisationRepository;
    private TownRepository $townRepository;

    public function __construct(
        Parser $parser,
        Offers $parserForOffers,
        LanguageHandling $language,
        OrganisationRepository $organisationRepository,
        TownRepository $townRepository
    ) {
        $this->parser = $parser;
        $this->parserForOffers = $parserForOffers;
        $this->language = $language;
        $this->organisationRepository = $organisationRepository;
        $this->townRepository = $townRepository;
    }

    public function convert(array $jsonLD, ImportConfiguration $configuration): EntityCollection
    {
        $manager = $this->organisationRepository->findOneByRemoteId($this->parser->getManagerId($jsonLD));
        $town = $this->townRepository->findOneByRemoteIds($this->parser->getContainedInPlaceIds($jsonLD));
        $entities = GeneralUtility::makeInstance(EntityCollection::class);
        $storagePid = $configuration->getStoragePid();

        foreach ($this->language->getLanguages($storagePid) as $language) {
            $title = $this->parser->getTitle($jsonLD, $language);
            if ($title === '') {
                continue;
            }

            $entity = GeneralUtility::makeInstance(
                GenericEntity::class,
                $storagePid,
                'tx_thuecat_tourist_attraction',
                $language->getLanguageId(),
                $this->parser->getId($jsonLD),
                [
                    'title' => $this->parser->getTitle($jsonLD, $language),
                    'description' => $this->parser->getDescription($jsonLD, $language),
                    'managed_by' => $manager ? $manager->getUid() : 0,
                    'town' => $town ? $town->getUid() : 0,
                    'opening_hours' => json_encode($this->parser->getOpeningHours($jsonLD)),
                    'address' => json_encode($this->parser->getAddress($jsonLD)),
                    'media' => json_encode($this->parser->getMedia($jsonLD)),
                    'offers' => json_encode($this->parserForOffers->get($jsonLD, $language)),
                ]
            );
            $entities->add($entity);
        }

        return $entities;
    }

    public function canConvert(array $type): bool
    {
        return array_search('schema:TouristAttraction', $type) !== false;
    }
}
