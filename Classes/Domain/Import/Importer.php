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
     * @var ImportLogRepository
     */
    private $importLogRepository;

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
        $this->import = new Import();
    }

    public function importConfiguration(ImportConfiguration $configuration): ImportLog
    {
        $this->import->start($configuration);
        $this->import();
        $this->import->end();

        if ($this->import->done()) {
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
        if ($this->import->handledRemoteId($url)) {
            return;
        }
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
        if ($this->entityAllowed($jsonEntity) === false) {
            return;
        }

        $targetEntity = $this->entityRegistry->getEntityByTypes($jsonEntity['@type']);
        if ($targetEntity === '') {
            return;
        }

        $entities = new EntityCollection();

        foreach ($this->languages->getAvailable($this->import->getConfiguration()) as $language) {
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
                $this->import->getConfiguration(),
                $language
            );

            if ($convertedEntity === null) {
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

        return false;
    }
}
