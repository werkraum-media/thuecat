<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ImportConfiguration extends AbstractEntity
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $configuration = '';

    /**
     * @var \DateTimeImmutable|null
     */
    protected $tstamp = null;

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

    public function getLastChanged(): ?\DateTimeImmutable
    {
        return $this->tstamp;
    }

    public function getStoragePid(): int
    {
        if ($this->configuration === '') {
            return 0;
        }

        $storagePid = ArrayUtility::getValueByPath(
            GeneralUtility::xml2array($this->configuration),
            'data/sDEF/lDEF/storagePid/vDEF'
        );

        if (is_numeric($storagePid) && $storagePid > 0) {
            return intval($storagePid);
        }

        return 0;
    }

    public function getUrls(): array
    {
        if ($this->configuration === '') {
            return [];
        }

        $entries = array_map(function (array $urlEntry) {
            return ArrayUtility::getValueByPath($urlEntry, 'url/el/url/vDEF');
        }, $this->getEntries());

        $entries = array_filter($entries);

        return array_values($entries);
    }

    public function getSyncScopeId(): string
    {
        if ($this->configuration === '') {
            return '';
        }

        $configurationAsArray = $this->getConfigurationAsArray();
        $arrayPath = 'data/sDEF/lDEF/syncScopeId/vDEF';

        if (ArrayUtility::isValidPath($configurationAsArray, $arrayPath) === false) {
            return '';
        }

        return ArrayUtility::getValueByPath(
            $configurationAsArray,
            $arrayPath
        );
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
}
