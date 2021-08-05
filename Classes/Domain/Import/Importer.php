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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\EntityRegistry;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Languages;
use WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry as UrlProviderRegistry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
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
     * @var ImportLog
     */
    private $importLog;

    /**
     * @var ImportLogRepository
     */
    private $importLogRepository;

    /**
     * @var ImportConfiguration
     */
    private $configuration;

    public function __construct(
        UrlProviderRegistry $urls,
        Converter $converter,
        EntityRegistry $entityRegistry,
        EntityMapper $entityMapper,
        Languages $languages,
        ImportLogRepository $importLogRepository,
        FetchData $fetchData,
        SaveData $saveData
    ) {
        $this->urls = $urls;
        $this->converter = $converter;
        $this->entityRegistry = $entityRegistry;
        $this->entityMapper = $entityMapper;
        $this->languages = $languages;
        $this->importLogRepository = $importLogRepository;
        $this->fetchData = $fetchData;
        $this->saveData = $saveData;
    }

    public function importConfiguration(ImportConfiguration $configuration): ImportLog
    {
        $this->configuration = $configuration;

        $this->importLog = GeneralUtility::makeInstance(ImportLog::class, $this->configuration);

        $urlProvider = $this->urls->getProviderForConfiguration($this->configuration);
        if (!$urlProvider instanceof UrlProvider) {
            return $this->importLog;
        }

        foreach ($urlProvider->getUrls() as $url) {
            $this->importResourceByUrl($url);
        }

        $this->importLogRepository->addLog($this->importLog);
        return clone $this->importLog;
    }

    private function importResourceByUrl(string $url): void
    {
        $content = $this->fetchData->jsonLDFromUrl($url);

        if ($content === []) {
            return;
        }

        foreach ($content['@graph'] as $jsonEntity) {
            $this->importJsonEntity($jsonEntity);
        }
    }

    private function importJsonEntity(array $jsonEntity): void
    {
        $targetEntity = $this->entityRegistry->getEntityByTypes($jsonEntity['@type']);
        if ($targetEntity === '') {
            return;
        }

        $entities = new EntityCollection();

        foreach ($this->languages->getAvailable($this->configuration) as $language) {
            $mappedEntity = $this->entityMapper->mapDataToEntity(
                $jsonEntity,
                $targetEntity,
                [
                    JsonDecode::ACTIVE_LANGUAGE => $language,
                ]
            );
            if (!$mappedEntity instanceof MapsToType) {
                continue;
            }
            $convertedEntity = $this->converter->convert(
                $mappedEntity,
                $this->configuration,
                $language
            );

            if ($convertedEntity === null) {
                continue;
            }
            $entities->add($convertedEntity);
        }

        $this->saveData->import(
            $entities,
            $this->importLog
        );
    }
}
