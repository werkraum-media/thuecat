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

namespace WerkraumMedia\ThueCat\Domain\Import\Typo3Converter;

use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MediaObject;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\PriceSpecification;
use WerkraumMedia\ThueCat\Domain\Import\Entity\TouristAttraction as TouristAttractionEntity;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

class TouristAttraction implements Converter
{
    /**
     * @var ResolveForeignReference
     */
    private $resolveForeignReference;

    /**
     * @var LanguageHandling
     */
    private $languageHandling;

    /**
     * @var OrganisationRepository
     */
    private $organisationRepository;

    /**
     * @var TownRepository
     */
    private $townRepository;

    public function __construct(
        ResolveForeignReference $resolveForeignReference,
        LanguageHandling $languageHandling,
        OrganisationRepository $organisationRepository,
        TownRepository $townRepository
    ) {
        $this->resolveForeignReference = $resolveForeignReference;
        $this->languageHandling = $languageHandling;
        $this->organisationRepository = $organisationRepository;
        $this->townRepository = $townRepository;
    }

    public function canConvert(MapsToType $entity): bool
    {
        return $entity instanceof TouristAttractionEntity;
    }

    public function convert(
        MapsToType $entity,
        ImportConfiguration $configuration,
        string $language
    ): ?Entity {
        if (!$entity instanceof TouristAttractionEntity) {
            throw new \InvalidArgumentException('Did not get entity of expected type.', 1628243431);
        }

        if ($entity->hasName() === false) {
            return null;
        }

        $manager = null;
        if ($entity->getManagedBy() instanceof ForeignReference) {
            $manager = $this->organisationRepository->findOneByRemoteId(
                $entity->getManagedBy()->getId()
            );
        }

        $town = $this->townRepository->findOneByEntity($entity);

        return new GenericEntity(
            $configuration->getStoragePid(),
            'tx_thuecat_tourist_attraction',
            $this->languageHandling->getLanguageUidForString(
                $configuration->getStoragePid(),
                $language
            ),
            $entity->getId(),
            [
                'title' => $entity->getName(),
                'description' => $entity->getDescription(),
                'slogan' => $entity->getSlogan(),
                'start_of_construction' => $entity->getStartOfConstruction(),
                'sanitation' => implode(',', $entity->getSanitations()),
                'managed_by' => $manager ? $manager->getUid() : 0,
                'town' => $town ? $town->getUid() : 0,
                'media' => $this->getMedia($entity, $language),
                'opening_hours' => $this->getOpeningHours($entity),
                'address' => $this->getAddress($entity),
                'offers' => $this->getOffers($entity),
                'other_service' => implode(',', $entity->getOtherServices()),
                'museum_service' => implode(',', $entity->getMuseumServices()),
                'architectural_style' => implode(',', $entity->getArchitecturalStyles()),
                'traffic_infrastructure' => implode(',', $entity->getTrafficInfrastructures()),
                'payment_accepted' => implode(',', $entity->getPaymentsAccepted()),
                'digital_offer' => implode(',', $entity->getDigitalOffers()),
                'photography' => implode(',', $entity->getPhotographies()),
                'pets_allowed' => $entity->getPetsAllowed(),
                'is_accessible_for_free' => $entity->getIsAccessibleForFree(),
            ]
        );
    }

    private function getMedia(
        TouristAttractionEntity $entity,
        string $language
    ): string {
        $data = [];

        if ($entity->getPhoto() instanceof ForeignReference) {
            $photo = $this->resolveForeignReference->resolve(
                $entity->getPhoto(),
                $language
            );
            if ($photo instanceof MediaObject) {
                $data[] = $this->getSingleMedia($photo, true);
            }
        }

        foreach ($entity->getImages() as $image) {
            $image = $this->resolveForeignReference->resolve(
                $image,
                $language
            );
            if ($image instanceof MediaObject) {
                $data[] = $this->getSingleMedia($image, false);
            }
        }

        return json_encode($data) ?: '';
    }

    private function getSingleMedia(
        MediaObject $mediaObject,
        bool $mainImage
    ): array {
        return [
            'mainImage' => $mainImage,
            'type' => $mediaObject->getType(),
            'title' => $mediaObject->getName(),
            'description' => $mediaObject->getDescription(),
            'url' => $mediaObject->getUrl(),
            'copyrightYear' => $mediaObject->getCopyrightYear(),
            'license' => [
                'type' => $mediaObject->getLicense(),
                'author' => $mediaObject->getLicenseAuthor(),
            ],
        ];
    }

    private function getOpeningHours(TouristAttractionEntity $entity): string
    {
        $data = [];

        foreach ($entity->getOpeningHoursSpecification() as $openingHour) {
            $data[] = [
                'opens' => $openingHour->getOpens()->format('H:i:s'),
                'closes' => $openingHour->getCloses()->format('H:i:s'),
                'from' => $openingHour->getValidFrom(),
                'through' => $openingHour->getValidThrough(),
                'daysOfWeek' => $openingHour->getDaysOfWeek(),
            ];
        }

        return json_encode($data) ?: '';
    }

    private function getAddress(TouristAttractionEntity $entity): string
    {
        $data = [];

        $address = $entity->getAddress();
        if ($address !== null) {
            $data += [
                'street' => $address->getStreetAddress(),
                'zip' => $address->getPostalCode(),
                'city' => $address->getAddressLocality(),
                'email' => $address->getEmail(),
                'phone' => $address->getTelephone(),
                'fax' => $address->getFaxNumber(),
            ];
        }

        $geo = $entity->getGeo();
        if ($geo !== null) {
            $data += [
                'geo' => [
                    'latitude' => $geo->getLatitude(),
                    'longitude' => $geo->getLongitude(),
                ],
            ];
        }

        return json_encode($data) ?: '';
    }

    private function getOffers(TouristAttractionEntity $entity): string
    {
        $data = [];
        foreach ($entity->getOffers() as $offer) {
            $data[] = [
                'title' => $offer->getName(),
                'description' => $offer->getDescription(),
                'prices' => array_map([$this, 'getPrice'], $offer->getPrices()),
            ];
        }

        return json_encode($data) ?: '';
    }

    private function getPrice(PriceSpecification $priceSpecification): array
    {
        return [
            'title' => $priceSpecification->getName(),
            'description' => $priceSpecification->getDescription(),
            'price' => $priceSpecification->getPrice(),
            'currency' => $priceSpecification->getCurrency(),
            'rule' => $priceSpecification->getCalculationRule(),
        ];
    }
}
