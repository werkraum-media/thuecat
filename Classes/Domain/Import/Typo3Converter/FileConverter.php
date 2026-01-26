<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Typo3Converter;

use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Resource\DuplicationBehavior as OldDuplicationBehavior;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MediaObject;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

final class FileConverter implements Converter
{
    public function __construct(
        private readonly StorageRepository $storageRepository,
        private readonly MetaDataRepository $metaDataRepository,
        private readonly ConfigurationManager $typo3ConfigurationManager,
        private readonly FetchData $fetchData,
    ) {
    }

    public function getSupportedEntities(): array
    {
        return [
            MediaObject::class,
        ];
    }

    public function convert(
        MapsToType $entity,
        ImportConfiguration $configuration,
        string $language
    ): ?Entity {
        if (!$entity instanceof MediaObject) {
            throw new \Exception('Got entity of unexpected type.', 1769412725);
        }

        // TODO: Handle languages, this is only for now during development
        if ($language !== 'de') {
            return null;
        }

        $url = $entity->getUrls()[0];
        $localFilePath = $this->storeTempFile($this->fetchData->loadFile($url));

        $file = $this->importFile($entity, $localFilePath);

        // TODO: Use DataHandler? To also allow creation of translations
        $this->metaDataRepository->update($file->getUid(), [
            'title' => $entity->getName(),
            'description' => $entity->getDescription(),
            // TODO: How to fetch or handle alternative?
            // 'alternative' => $mediaObject['description'] ?? '',
            // TODO: Move to constant.
            'publisher' => 'thuecat.org',
            'source' => $entity->getId(),
            'copyright' => $this->createCopyright($entity),
            // TODO: TYPO3 does not offer to save a license?
        ]);

        $entity = new GenericEntity(
            $configuration->getStoragePid(),
            'sys_file',
            0,
            $entity->getId(),
            []
        );

        // TODO: Make depend on whether it already existed.
        $entity->setImportedTypo3Uid($file->getUid());

        return $entity;
    }

    private function storeTempFile(string $fileContent): string
    {
        $temporaryFilename = GeneralUtility::tempnam('thuecat');

        $writeResult = GeneralUtility::writeFile($temporaryFilename, $fileContent, true);
        if ($writeResult === false) {
            throw new Exception('Could not write temporary file: ' . $temporaryFilename);
        }

        return $temporaryFilename;
    }

    private function importFile(MediaObject $entity, string $localFilePath): File
    {
        // TODO: Use configuration to fetch storage
        $storage = $this->storageRepository->getDefaultStorage();
        // TODO: Use configuration to fetch folder
        $folderPath = '/editors/thuecat/';

        $fileName = hash('sha256', $entity->getId()) . '.png';
        $fileName = $this->ensureFileNameOfImageMatchesMimeType($localFilePath, $fileName);

        if ($storage->hasFolder($folderPath)) {
            $folder = $storage->getFolder($folderPath);
        } else {
            $folder = $storage->createFolder($folderPath);
        }

        $behaviour = OldDuplicationBehavior::REPLACE;
        if (class_exists(DuplicationBehavior::class)) {
            $behaviour = DuplicationBehavior::REPLACE;
        }

        return $folder->addFile($localFilePath, $fileName, $behaviour);
    }

    private function ensureFileNameOfImageMatchesMimeType(string $filePath, string $fileName): string
    {
        $imageFileExtensions = $this->typo3ConfigurationManager->getConfigurationValueByPath('GFX/imagefile_ext');
        if (is_string($imageFileExtensions) === false) {
            return '';
        }

        $fileNameParts = pathinfo($fileName);
        $imageFileExtensions = GeneralUtility::trimExplode(',', $imageFileExtensions);
        $actualExtensions = (new FileInfo($filePath))->getMimeExtensions();

        if (
            array_key_exists('extension', $fileNameParts)
            && in_array($fileNameParts['extension'], $imageFileExtensions, true)
            && in_array($fileNameParts['extension'], $actualExtensions, true)
        ) {
            return $fileName;
        }

        foreach ($actualExtensions as $possibleExtension) {
            if (in_array($possibleExtension, $imageFileExtensions, true) === false) {
                continue;
            }

            return $fileNameParts['filename'] . '.' . $possibleExtension;
        }

        return '';
    }

    private function createCopyright(MediaObject $entity): string
    {
        $copyright = [
            $entity->getCopyrightYear(),
        ];

        return implode(' ', array_filter($copyright));
    }
}
