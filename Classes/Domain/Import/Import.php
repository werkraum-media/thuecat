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

use InvalidArgumentException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration as Typo3ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;

/**
 * State of an import.
 *
 * Imports can be nested, e.g. an entity during import triggers an import of sub entities.
 * The state is handled here to allow a single import result with all affected entities and errors.
 */
class Import
{
    /**
     * @var ImportLog[]
     */
    private $importLogStack = [];

    /**
     * @var ImportConfiguration[]
     */
    private $configurationStack = [];

    /**
     * @var ImportLog
     */
    private $currentImportLog;

    /**
     * @var ImportConfiguration
     */
    private $currentConfiguration;

    public function start(ImportConfiguration $configuration): void
    {
        if (!$configuration instanceof Typo3ImportConfiguration) {
            throw new InvalidArgumentException('Currently only can process ImportConfiguration of TYPO3.', 1629708772);
        }

        $this->currentConfiguration = $configuration;
        $this->currentImportLog = new ImportLog($configuration);

        $this->configurationStack[] = $this->currentConfiguration;
        $this->importLogStack[] = $this->currentImportLog;
    }

    public function end(): void
    {
        array_pop($this->configurationStack);
        $outerConfiguration = end($this->configurationStack);
        if ($outerConfiguration instanceof ImportConfiguration) {
            $this->currentConfiguration = $outerConfiguration;
        }

        $lastImportLog = array_pop($this->importLogStack);
        $outerImportLog = end($this->importLogStack);
        if ($outerImportLog instanceof ImportLog) {
            $this->currentImportLog = $outerImportLog;
        }
        if ($lastImportLog instanceof ImportLog) {
            $this->currentImportLog->merge($lastImportLog);
        }
    }

    public function done(): bool
    {
        return $this->importLogStack === [];
    }

    public function getConfiguration(): ImportConfiguration
    {
        return $this->currentConfiguration;
    }

    public function getLog(): ImportLog
    {
        return $this->currentImportLog;
    }

    public function handledRemoteId(string $remoteId): bool
    {
        // Tours are not supported yet.
        // So skip them here to save time.
        if (str_ends_with($remoteId, '-oatour')) {
            return true;
        }

        foreach ($this->importLogStack as $importLog) {
            if ($importLog->handledRemoteId($remoteId)) {
                return true;
            }
        }

        return false;
    }
}
