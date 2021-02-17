<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

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
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Registry as ConverterRegistry;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry as UrlProviderRegistry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;

class Importer
{
    private UrlProviderRegistry $urls;
    private ConverterRegistry $converter;
    private FetchData $fetchData;
    private SaveData $saveData;
    private ImportLog $importLog;
    private ImportLogRepository $importLogRepository;
    private ImportConfiguration $configuration;

    public function __construct(
        UrlProviderRegistry $urls,
        ConverterRegistry $converter,
        ImportLogRepository $importLogRepository,
        FetchData $fetchData,
        SaveData $saveData
    ) {
        $this->urls = $urls;
        $this->converter = $converter;
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
        $converter = $this->converter->getConverterBasedOnType($jsonEntity['@type']);
        if ($converter instanceof Converter) {
            $entities = $converter->convert($jsonEntity, $this->configuration);
            $this->saveData->import($entities, $this->importLog);
            return;
        }
    }
}
