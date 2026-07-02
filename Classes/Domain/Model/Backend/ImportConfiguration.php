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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

use DateTimeImmutable;
use Exception;
use RuntimeException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ImportConfiguration extends AbstractEntity implements ImportConfigurationInterface
{
    protected string $title = '';

    protected string $type = '';

    protected string $configuration = '';

    protected ?DateTimeImmutable $tstamp = null;

    /**
     * @var ObjectStorage<ImportLog>
     */
    protected ObjectStorage $logs;

    /**
     * @var string[]|null
     */
    protected ?array $urls = null;

    /**
     * @var string[]
     */
    protected array $allowedTypes = [];

    public function __construct()
    {
        $this->logs = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTableName(): string
    {
        return 'tx_thuecat_import_configuration';
    }

    public function getLastChanged(): ?DateTimeImmutable
    {
        return $this->tstamp;
    }

    public function getLastImported(): ?DateTimeImmutable
    {
        $lastImport = null;
        $positionOfLastLog = count($this->logs) - 1;
        if ($this->logs->offsetExists($positionOfLastLog)) {
            $lastImport = $this->logs->offsetGet($positionOfLastLog);
        }
        if (!$lastImport instanceof ImportLog) {
            return null;
        }

        return $lastImport->getCreated();
    }

    public function getStoragePid(): int
    {
        $storagePid = $this->getConfigurationValueFromFlexForm('storagePid');

        if (is_numeric($storagePid) && $storagePid > 0) {
            return (int)$storagePid;
        }

        return 0;
    }

    public function getFileFolder(): string
    {
        $fileFolder = $this->getConfigurationValueFromFlexForm('fileFolder');
        return is_string($fileFolder) ? $fileFolder : '';
    }

    public function getUrls(): array
    {
        if ($this->urls !== null) {
            return $this->urls;
        }

        if ($this->configuration === '') {
            return [];
        }

        $entries = array_map(function (array $urlEntry) {
            return ArrayUtility::getValueByPath($urlEntry, 'url/el/url/vDEF');
        }, $this->getEntries());

        $entries = array_filter($entries);

        return array_values($entries);
    }

    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function getApiKey(): string
    {
        $apiKey = $this->getConfigurationValueFromFlexForm('apiKey');
        return is_string($apiKey) ? $apiKey : '';
    }

    public function getSyncScopeId(): string
    {
        return $this->getConfigurationValueFromFlexForm('syncScopeId');
    }

    public function getApiDomain(): string
    {
        // Returns the user's explicit choice or '' when blank/missing. The
        // default (FetchData::DEFAULT_API_DOMAIN) is applied downstream by
        // FetchData itself — keeping this method honest lets
        // FetchData::getFullResourceUrl() distinguish "no override → use
        // canonical resource host" from "explicit override → co-locate
        // resource fetches at that host".
        $apiDomain = $this->getConfigurationValueFromFlexForm('apiDomain');
        return is_string($apiDomain) ? $apiDomain : '';
    }

    public function getImportTarget(): string
    {
        $importTarget = $this->getConfigurationValueFromFlexForm('importTarget');
        // syncScope flexform values are extension keys ('thuecat', 'events').
        // Non-syncScope configurations don't carry the field — they import
        // ThueCat POI structures by definition, so 'thuecat' is the safe
        // historical default.
        return is_string($importTarget) && $importTarget !== '' ? $importTarget : 'thuecat';
    }

    public function getFetchLastXDays(): int
    {
        $value = $this->getConfigurationValueFromFlexForm('fetchLastXDays');
        if (!is_scalar($value) || $value === '') {
            return 1;
        }
        return (int)$value;
    }

    public function getContainsPlaceId(): string
    {
        $containsPlaceId = $this->getConfigurationValueFromFlexForm('containsPlaceId');
        if (!is_string($containsPlaceId)) {
            throw new Exception('Could not fetch containsPlaceId.', 1671027015);
        }
        return $containsPlaceId;
    }

    private function getEntries(): array
    {
        $configurationAsArray = $this->getConfigurationAsArray();

        if (ArrayUtility::isValidPath($configurationAsArray, 'data/sDEF/lDEF/urls/el') === false) {
            return [];
        }

        return ArrayUtility::getValueByPath(
            $configurationAsArray,
            'data/sDEF/lDEF/urls/el'
        );
    }

    private function getConfigurationAsArray(): array
    {
        $asArray = GeneralUtility::xml2array($this->configuration);

        if (is_array($asArray) === false) {
            throw new RuntimeException('Could not parse the configuration: ' . $asArray, 1729148214);
        }

        return $asArray;
    }

    /**
     * @return mixed
     */
    private function getConfigurationValueFromFlexForm(string $fieldName)
    {
        if ($this->configuration === '') {
            return '';
        }

        $configurationAsArray = $this->getConfigurationAsArray();
        $arrayPath = 'data/sDEF/lDEF/' . $fieldName . '/vDEF';

        if (ArrayUtility::isValidPath($configurationAsArray, $arrayPath) === false) {
            return '';
        }

        return ArrayUtility::getValueByPath(
            $configurationAsArray,
            $arrayPath
        );
    }
}
