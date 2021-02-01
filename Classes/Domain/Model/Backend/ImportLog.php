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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as Typo3AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ImportLog extends Typo3AbstractEntity
{
    /**
     * @var ObjectStorage<ImportLogEntry>
     */
    protected $logEntries = [];

    /**
     * @var ImportConfiguration
     */
    protected $configuration;

    /**
     * @var \DateTimeImmutable|null
     */
    protected $crdate;

    public function __construct(
        ImportConfiguration $configuration
    ) {
        $this->logEntries = new ObjectStorage();
        $this->configuration = $configuration;
    }

    public function addEntry(ImportLogEntry $entry): void
    {
        $this->logEntries->attach($entry);
    }

    public function getConfiguration(): ImportConfiguration
    {
        return $this->configuration;
    }

    /**
     * @return ObjectStorage<ImportLogEntry>
     */
    public function getEntries(): ObjectStorage
    {
        return $this->logEntries;
    }

    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->crdate;
    }

    public function getListOfErrors(): array
    {
        $errors = [];

        foreach ($this->getEntries() as $entry) {
            if ($entry->hasErrors()) {
                $errors = array_merge($errors, $entry->getErrors());
            }
        }

        return $errors;
    }

    public function hasErrors(): bool
    {
        foreach ($this->getEntries() as $entry) {
            if ($entry->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    public function getSummaryOfEntries(): array
    {
        $summary = [];

        foreach ($this->getEntries() as $entry) {
            if (isset($summary[$entry->getRecordDatabaseTableName()])) {
                ++$summary[$entry->getRecordDatabaseTableName()];
                continue;
            }
            $summary[$entry->getRecordDatabaseTableName()] = 1;
        }

        return $summary;
    }
}
