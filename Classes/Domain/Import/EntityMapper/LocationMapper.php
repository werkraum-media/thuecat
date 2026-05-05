<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\EntityMapper;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\EventLocation;
use WerkraumMedia\ThueCat\Domain\Import\Model\LocationEntity;

class LocationMapper
{
    public function map(
        EventLocation $location,
        int $storagePid,
        int $languageUid,
        string $eventRemoteId,
    ): LocationEntity {
        $address = $location->getAddress();
        $geo = $location->getGeo();

        return new LocationEntity(
            $storagePid,
            $languageUid,
            $location->getName(),
            $address?->getStreetAddress() ?? '',
            $address?->getPostalCode() ?? '',
            $address?->getAddressLocality() ?? '',
            '',
            $address?->getTelephone() ?? '',
            (string)$geo?->getLatitude(),
            (string)$geo?->getLongitude(),
            $eventRemoteId . '#location',
        );
    }
}
