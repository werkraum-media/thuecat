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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration as ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;

class ImportConfiguration extends AbstractEntity implements ImportConfigurationInterface
{
    protected string $title = '';

    protected string $type = '';

    protected string $configuration = '';

    protected ?DateTimeImmutable $tstamp;

    /**
     * @var ObjectStorage<ImportLog>
     */
    protected ObjectStorage $logs;

    /**
     * @var string[]|null
     */
    protected ?array $urls;

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

    public function getSyncScopeId(): string
    {
        return $this->getConfigurationValueFromFlexForm('syncScopeId');
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
        return GeneralUtility::xml2array($this->configuration);
    }

    /**
     * @param ForeignReference[] $foreignReferences
     */
    public static function createFromBaseWithForeignReferences(
        self $base,
        array $foreignReferences,
        array $allowedTypes = []
    ): self {
        $configuration = clone $base;
        $configuration->urls = ResolveForeignReference::convertToRemoteIds($foreignReferences);
        $configuration->type = 'static';
        $configuration->allowedTypes = $allowedTypes;
        return $configuration;
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
