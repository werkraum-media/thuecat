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

namespace WerkraumMedia\ThueCat\Domain\Import;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\EntityRegistry;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\MappingException;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Languages;
use WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry as UrlProviderRegistry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\MappingError;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;

class Importer
{
    /**
     * @var UrlProviderRegistry
     */
    private $urls;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var EntityRegistry
     */
    private $entityRegistry;

    /**
     * @var EntityMapper
     */
    private $entityMapper;

    /**
     * @var Languages
     */
    private $languages;

    /**
     * @var FetchData
     */
    private $fetchData;

    /**
     * @var SaveData
     */
    private $saveData;

    /**
     * @var ImportLogRepository
     */
    private $importLogRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Import
     */
    private $import;

    public function __construct(
        UrlProviderRegistry $urls,
        Converter $converter,
        EntityRegistry $entityRegistry,
        EntityMapper $entityMapper,
        Languages $languages,
        ImportLogRepository $importLogRepository,
        FetchData $fetchData,
        SaveData $saveData,
        LogManager $logManager
    ) {
        $this->urls = $urls;
        $this->converter = $converter;
        $this->entityRegistry = $entityRegistry;
        $this->entityMapper = $entityMapper;
        $this->languages = $languages;
        $this->importLogRepository = $importLogRepository;
        $this->fetchData = $fetchData;
        $this->saveData = $saveData;
        $this->logger = $logManager->getLogger(__CLASS__);
        $this->import = new Import();
    }

    public function importConfiguration(ImportConfiguration $configuration): ImportLog
    {
        $this->import->start($configuration);
        $this->import();
        $this->import->end();

        if ($this->import->done()) {
            $this->logger->info(
                'Finished import.',
                [
                    'errors' => $this->import->getLog()->getListOfErrors(),
                    'summary' => $this->import->getLog()->getSummaryOfEntries(),
                ]
            );
            $this->importLogRepository->addLog($this->import->getLog());
        }

        return $this->import->getLog();
    }

    private function import(): void
    {
        $urlProvider = $this->urls->getProviderForConfiguration($this->import->getConfiguration());
        if (!$urlProvider instanceof UrlProvider) {
            throw new \Exception('No URL Provider available for given configuration.', 1629296635);
        }

        foreach ($urlProvider->getUrls() as $url) {
            $this->importResourceByUrl($url);
        }
    }

    private function importResourceByUrl(string $url): void
    {
        $this->logger->info('Process url.', ['url' => $url]);
        if ($this->import->handledRemoteId($url)) {
            $this->logger->notice('Skip Url as we already handled it during import.', ['url' => $url]);
            return;
        }
        $content = $this->fetchData->jsonLDFromUrl($url);

        if ($content === []) {
            $this->logger->notice('Skip Url as we did not receive any content.', ['url' => $url]);
            return;
        }

        foreach ($content['@graph'] as $jsonEntity) {
            $this->importJsonEntity($jsonEntity, $url);
        }
    }

    private function importJsonEntity(array $jsonEntity, string $url): void
    {
        if ($this->entityAllowed($jsonEntity) === false) {
            return;
        }

        $targetEntity = $this->entityRegistry->getEntityByTypes($jsonEntity['@type']);
        if ($targetEntity === '') {
            $this->logger->notice('Skip entity, no target entity found.', ['types' => $jsonEntity['@type']]);
            return;
        }

        $entities = new EntityCollection();

        foreach ($this->languages->getAvailable($this->import->getConfiguration()) as $language) {
            $this->logger->info('Process entity for language.', ['language' => $language, 'targetEntity' => $targetEntity]);
            try {
                $mappedEntity = $this->entityMapper->mapDataToEntity(
                    $jsonEntity,
                    $targetEntity,
                    [
                        JsonDecode::ACTIVE_LANGUAGE => $language,
                    ]
                );
            } catch (MappingException $e) {
                $this->handleMappingException($e, $language);
                continue;
            }

            if (!$mappedEntity instanceof MapsToType) {
                $this->logger->error('Mapping did not result in an MapsToType instance.', ['class' => get_class($mappedEntity)]);
                continue;
            }

            try {
                $convertedEntity = $this->converter->convert(
                    $mappedEntity,
                    $this->import->getConfiguration(),
                    $language
                );
            } catch (MappingException $e) {
                $this->handleMappingException($e, $language);
                $convertedEntity = null;
            }

            if ($convertedEntity === null) {
                $this->logger->notice(
                    'Could not convert entity.',
                    [
                        'url' => $url,
                        'language' => $language,
                        'targetEntity' => $targetEntity,
                    ]);
                continue;
            }
            $entities->add($convertedEntity);
        }

        $this->saveData->import(
            $entities,
            $this->import->getLog()
        );
    }

    private function entityAllowed(array $jsonEntity): bool
    {
        if ($this->import->getConfiguration()->getAllowedTypes() === []) {
            return true;
        }

        foreach ($jsonEntity['@type'] as $type) {
            if (in_array($type, $this->import->getConfiguration()->getAllowedTypes()) === true) {
                return true;
            }
        }

        $this->logger->notice('Deny entity as type is not allowed.', ['types' => $jsonEntity['@type']]);
        return false;
    }

    private function handleMappingException(MappingException $exception, string $language): void
    {
        $this->logger->error('Could not map data to entity.', [
            'url' => $exception->getUrl(),
            'language' => $language,
            'mappingError' => $exception->getMessage(),
        ]);
        $this->import->getLog()->addEntry(new MappingError($exception));
    }
}
