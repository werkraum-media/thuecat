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
    protected string $title = '';
    protected string $type = '';
    protected string $configuration = '';
    protected ?\DateTimeImmutable $tstamp = null;

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

    public function getUrls(): array
    {
        if ($this->configuration === '') {
            return [];
        }

        $entries = array_map(function (array $urlEntry) {
            return ArrayUtility::getValueByPath($urlEntry, 'url/el/url/vDEF');
        }, $this->getEntries());

        return array_values($entries);
    }

    private function getEntries(): array
    {
        return ArrayUtility::getValueByPath(
            GeneralUtility::xml2array($this->configuration),
            'data/sDEF/lDEF/urls/el'
        );
    }
}
