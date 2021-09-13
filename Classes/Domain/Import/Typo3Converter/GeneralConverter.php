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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use WerkraumMedia\ThueCat\Domain\Import\Entity\AccessibilitySpecification;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Base;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MediaObject;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Minimum;
use WerkraumMedia\ThueCat\Domain\Import\Entity\ParkingFacility;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Place;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\PriceSpecification;
use WerkraumMedia\ThueCat\Domain\Import\Entity\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Import\Entity\TouristInformation;
use WerkraumMedia\ThueCat\Domain\Import\Entity\TouristMarketingCompany;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Town;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ParkingFacilityRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

class GeneralConverter implements Converter, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResolveForeignReference
     */
    private $resolveForeignReference;

    /**
     * @var Importer
     */
    private $importer;

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

    /**
     * @var ParkingFacilityRepository
     */
    private $parkingFacilityRepository;

    /**
     * @var ImportConfiguration
     */
    private $importConfiguration;

    /**
     * @var string[]
     */
    private $classToTableMapping = [
        TouristAttraction::class => 'tx_thuecat_tourist_attraction',
        ParkingFacility::class => 'tx_thuecat_parking_facility',
        Town::class => 'tx_thuecat_town',
        TouristInformation::class => 'tx_thuecat_tourist_information',
        TouristMarketingCompany::class => 'tx_thuecat_organisation',
    ];

    public function __construct(
        ResolveForeignReference $resolveForeignReference,
        Importer $importer,
        LanguageHandling $languageHandling,
        OrganisationRepository $organisationRepository,
        TownRepository $townRepository,
        ParkingFacilityRepository $parkingFacilityRepository
    ) {
        $this->resolveForeignReference = $resolveForeignReference;
        $this->importer = $importer;
        $this->languageHandling = $languageHandling;
        $this->organisationRepository = $organisationRepository;
        $this->townRepository = $townRepository;
        $this->parkingFacilityRepository = $parkingFacilityRepository;
    }

    public function convert(
        MapsToType $entity,
        ImportConfiguration $importConfiguration,
        string $language
    ): ?Entity {
        $this->importConfiguration = $importConfiguration;

        if ($this->shouldConvert($entity, $importConfiguration, $language) === false) {
            return null;
        }

        $converted = new GenericEntity(
            $importConfiguration->getStoragePid(),
            $this->getTableNameByEntityClass(get_class($entity)),
            $this->languageHandling->getLanguageUidForString(
                $importConfiguration->getStoragePid(),
                $language
            ),
            $entity->getId(),
            $this->buildDataArrayFromEntity(
                $entity,
                $language
            )
        );
        $this->logger->debug('Converted Entity', [
            'remoteId' => $entity->getId(),
            'storagePid' => $converted->getTypo3StoragePid(),
            'table' => $converted->getTypo3DatabaseTableName(),
            'language' => $converted->getTypo3SystemLanguageUid(),
        ]);
        return $converted;
    }

    private function shouldConvert(
        MapsToType $entity,
        ImportConfiguration $importConfiguration,
        string $language
    ): bool {
        $tableName = $this->getTableNameByEntityClass(get_class($entity));

        if (!$entity instanceof Minimum) {
            $this->logger->debug('Skipped conversion of entity, got unexpected type', [
                'expectedType' => Minimum::class,
                'actualType' => get_class($entity),
            ]);
            return false;
        }
        if ($entity->hasName() === false) {
            $this->logger->debug('Skipped conversion of entity, had no name', [
                'remoteId' => $entity->getId(),
            ]);
            return false;
        }

        $languageUid = $this->languageHandling->getLanguageUidForString(
            $importConfiguration->getStoragePid(),
            $language
        );
        if (
            $languageUid > 0
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) === false
        ) {
            $this->logger->debug('Skipped conversion of entity, table does not support translations', [
                'remoteId' => $entity->getId(),
                'requestedLanguage' => $language,
                'resolvedLanguageUid' => $languageUid,
                'resolvedTableName' => $tableName,
            ]);
            return false;
        }

        if ($tableName !== 'tx_thuecat_organisation' && $this->getManagerUid($entity) === '') {
            $this->logger->debug('Skipped conversion of entity, is not an organisation and no manager was available', [
                'remoteId' => $entity->getId(),
            ]);
            return false;
        }

        return true;
    }

    private function getTableNameByEntityClass(string $className): string
    {
        $tableName = $this->classToTableMapping[$className] ?? '';
        if ($tableName == '') {
            throw new \Exception('No table name configured for class ' . $className, 1629376990);
        }

        return $tableName;
    }

    private function buildDataArrayFromEntity(
        Minimum $entity,
        string $language
    ): array {
        return [
            'title' => $entity->getName(),
            'description' => trim($entity->getDescription()),
            'sanitation' => method_exists($entity, 'getSanitations') ? implode(',', $entity->getSanitations()) : '',
            'managed_by' => $this->getManagerUid($entity),
            'town' => $this->getTownUid($entity),
            'media' => $entity instanceof Base ? $this->getMedia($entity, $language) : '',

            'parking_facility_near_by' => $entity instanceof Base ? implode(',', $this->getParkingFacilitiesNearByUids($entity)) : '',

            'opening_hours' => $entity instanceof Place ? $this->getOpeningHours($entity) : '',
            'address' => $entity instanceof Place ? $this->getAddress($entity) : '',
            'offers' => $entity instanceof Place ? $this->getOffers($entity) : '',
            'other_service' => method_exists($entity, 'getOtherServices') ? implode(',', $entity->getOtherServices()) : '',
            'traffic_infrastructure' => method_exists($entity, 'getTrafficInfrastructures') ? implode(',', $entity->getTrafficInfrastructures()) : '',
            'payment_accepted' => method_exists($entity, 'getPaymentsAccepted') ? implode(',', $entity->getPaymentsAccepted()) : '',
            'distance_to_public_transport' => method_exists($entity, 'getDistanceToPublicTransport') ? $entity->getDistanceToPublicTransport() : '',

            'slogan' => method_exists($entity, 'getSlogan') ? $entity->getSlogan() : '',
            'start_of_construction' => method_exists($entity, 'getStartOfConstruction') ? $entity->getStartOfConstruction() : '',
            'museum_service' => method_exists($entity, 'getMuseumServices') ? implode(',', $entity->getMuseumServices()) : '',
            'architectural_style' => method_exists($entity, 'getArchitecturalStyles') ? implode(',', $entity->getArchitecturalStyles()) : '',
            'digital_offer' => method_exists($entity, 'getDigitalOffers') ? implode(',', $entity->getDigitalOffers()) : '',
            'photography' => method_exists($entity, 'getPhotographies') ? implode(',', $entity->getPhotographies()) : '',
            'pets_allowed' => method_exists($entity, 'getPetsAllowed') ? $entity->getPetsAllowed() : '',
            'is_accessible_for_free' => method_exists($entity, 'getIsAccessibleForFree') ? $entity->getIsAccessibleForFree() : '',
            'public_access' => method_exists($entity, 'getPublicAccess') ? $entity->getPublicAccess() : '',
            'available_languages' => method_exists($entity, 'getAvailableLanguages') ? implode(',', $entity->getAvailableLanguages()) : '',

            'accessibility_specification' => $this->getAccessibilitySpecification($entity, $language),
        ];
    }

    private function getManagerUid(object $entity): string
    {
        if (
            method_exists($entity, 'getManagedBy') === false
            || !$entity->getManagedBy() instanceof ForeignReference
        ) {
            return '';
        }

        $this->importer->importConfiguration(
            ImportConfiguration::createFromBaseWithForeignReferences(
                $this->importConfiguration,
                [$entity->getManagedBy()]
            )
        );
        $manager = $this->organisationRepository->findOneByRemoteId(
            $entity->getManagedBy()->getId()
        );

        return $manager ? (string)$manager->getUid() : '';
    }

    private function getTownUid(object $entity): string
    {
        if (
            $entity instanceof Town
            || method_exists($entity, 'getContainedInPlaces') === false
        ) {
            return '';
        }

        $this->importer->importConfiguration(
            ImportConfiguration::createFromBaseWithForeignReferences(
                $this->importConfiguration,
                $entity->getContainedInPlaces(),
                ['thuecat:Town']
            )
        );
        $town = $this->townRepository->findOneByEntity($entity);
        return $town ? (string) $town->getUid() : '';
    }

    private function getParkingFacilitiesNearByUids(Base $entity): array
    {
        if (method_exists($entity, 'getParkingFacilitiesNearBy') === false) {
            return [];
        }

        $this->importer->importConfiguration(
            ImportConfiguration::createFromBaseWithForeignReferences(
                $this->importConfiguration,
                $entity->getParkingFacilitiesNearBy()
            )
        );

        return $this->getUids(
            $this->parkingFacilityRepository->findByEntity($entity)
        );
    }

    private function getAccessibilitySpecification(
        object $entity,
        string $language
    ): string {
        if (
            method_exists($entity, 'getAccessibilitySpecification') === false
            || $entity->getAccessibilitySpecification() === null
        ) {
            return '{}';
        }

        $access = $this->resolveForeignReference->resolve(
            $entity->getAccessibilitySpecification(),
            $language
        );
        if (!$access instanceof AccessibilitySpecification) {
            return '{}';
        }

        $cert = $access->getAccessibilityCertification();

        $result = json_encode(array_filter([
            'accessibilityCertificationStatus' => $cert ? $cert->getAccessibilityCertificationStatus() : '',
            'certificationAccessibilityDeaf' => $cert ? $cert->getCertificationAccessibilityDeaf() : '',
            'certificationAccessibilityMental' => $cert ? $cert->getCertificationAccessibilityMental() : '',
            'certificationAccessibilityPartiallyDeaf' => $cert ? $cert->getCertificationAccessibilityPartiallyDeaf() : '',
            'certificationAccessibilityPartiallyVisual' => $cert ? $cert->getCertificationAccessibilityPartiallyVisual() : '',
            'certificationAccessibilityVisual' => $cert ? $cert->getCertificationAccessibilityVisual() : '',
            'certificationAccessibilityWalking' => $cert ? $cert->getCertificationAccessibilityWalking() : '',
            'certificationAccessibilityWheelchair' => $cert ? $cert->getCertificationAccessibilityWheelchair() : '',
            'accessibilitySearchCriteria' => $access->getAccessibilitySearchCriteria(),
            'shortDescriptionAccessibilityAllGenerations' => $access->getShortDescriptionAccessibilityAllGenerations(),
            'shortDescriptionAccessibilityAllergic' => $access->getShortDescriptionAccessibilityAllergic(),
            'shortDescriptionAccessibilityDeaf' => $access->getShortDescriptionAccessibilityDeaf(),
            'shortDescriptionAccessibilityMental' => $access->getShortDescriptionAccessibilityMental(),
            'shortDescriptionAccessibilityVisual' => $access->getShortDescriptionAccessibilityVisual(),
            'shortDescriptionAccessibilityWalking' => $access->getShortDescriptionAccessibilityWalking(),
        ]));
        if ($result === false || $result === '[]') {
            return '{}';
        }
        return $result;
    }

    private function getMedia(
        Base $entity,
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
            'url' => $mediaObject->getUrls()[0] ?? '',
            'copyrightYear' => $mediaObject->getCopyrightYear(),
            'license' => [
                'type' => $mediaObject->getLicense(),
                'author' => $mediaObject->getLicenseAuthor(),
            ],
        ];
    }

    private function getOpeningHours(Place $entity): string
    {
        $data = [];

        foreach ($entity->getOpeningHoursSpecification() as $openingHour) {
            $data[] = array_filter([
                'opens' => $openingHour->getOpens()->format('H:i:s'),
                'closes' => $openingHour->getCloses()->format('H:i:s'),
                'from' => $openingHour->getValidFrom() ?? '',
                'through' => $openingHour->getValidThrough() ?? '',
                'daysOfWeek' => $openingHour->getDaysOfWeek(),
            ]);
        }

        return json_encode($data) ?: '';
    }

    private function getAddress(Place $entity): string
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

    private function getOffers(Place $entity): string
    {
        $data = [];
        foreach ($entity->getOffers() as $offer) {
            $data[] = [
                'type' => $offer->getOfferType(),
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
            'rule' => implode(',', $priceSpecification->getCalculationRules()),
        ];
    }

    private function getUids(?QueryResultInterface $result): array
    {
        if ($result === null) {
            return [];
        }

        $uids = [];
        foreach ($result as $entry) {
            $uids[] = $entry->getUid();
        }
        return $uids;
    }
}
