<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Typo3Converter;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Event;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\LocationMapper;
use WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\Model\EventEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;

class EventConverter implements Converter
{
    public function __construct(
        private readonly LanguageHandling $languageHandling,
        private readonly LocationMapper $locationMapper,
        private readonly SaveData $saveData,
    ) {
    }

    public function supports(MapsToType $entity): bool
    {
        return $entity instanceof Event;
    }

    public function convert(
        MapsToType $entity,
        ImportConfiguration $importConfiguration,
        string $language
    ): ?Entity {
        if (!$entity instanceof Event) {
            return null;
        }

        if ($entity->hasName() === false) {
            return null;
        }

        $languageUid = $this->languageHandling->getLanguageUidForString(
            $importConfiguration->getStoragePid(),
            $language
        );

        $eventEntity = new EventEntity(
            $importConfiguration->getStoragePid(),
            $languageUid,
            $entity->getId(),
            $entity->getName(),
            trim($entity->getDescription()),
            $entity->getPriceInfo(),
            $entity->getWeb(),
            $entity->getTicket(),
            $entity->getKeywords(),
            sourceUrl: $entity->getId(),
        );

        $location = $entity->getLocation();
        if ($location !== null) {
            $locationEntity = $this->locationMapper->map(
                $location,
                $importConfiguration->getStoragePid(),
                $languageUid,
                $entity->getId(),
            );
            $locationCollection = new EntityCollection();
            $locationCollection->add($locationEntity);
            $this->saveData->import($locationCollection, new ImportLog($importConfiguration));
            if ($locationEntity->exists()) {
                $eventEntity->setLocationUid($locationEntity->getTypo3Uid());
            }
        }

        return $eventEntity;
    }
}
