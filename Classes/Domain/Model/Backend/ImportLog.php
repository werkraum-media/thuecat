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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as Typo3AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;

class ImportLog extends Typo3AbstractEntity
{
    /**
     * @var ObjectStorage<ImportLogEntry>
     */
    protected ObjectStorage $logEntries;

    protected ?DateTimeImmutable $crdate = null;

    public function __construct(
        protected ?ImportConfiguration $configuration = null
    ) {
        $this->logEntries = new ObjectStorage();
    }

    public function addEntry(ImportLogEntry $entry): void
    {
        $this->logEntries->attach($entry);
    }

    public function getConfiguration(): ?ImportConfiguration
    {
        return $this->configuration;
    }

    public function getConfigurationUid(): int
    {
        if ($this->configuration instanceof ImportConfiguration) {
            $uid = $this->configuration->getUid();
        }

        return $uid ?? 0;
    }

    /**
     * @return ObjectStorage<ImportLogEntry>
     */
    public function getEntries(): ObjectStorage
    {
        return $this->logEntries;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->crdate;
    }

    public function getListOfErrors(): array
    {
        $errors = [];

        foreach ($this->getEntries() as $entry) {
            if ($entry->hasErrors()) {
                $entryErrors = array_map(function (string $error) use ($entry) {
                    return 'Resource: ' . $entry->getRemoteId() . ' Error: ' . $error;
                }, $entry->getErrors());

                $errors = array_merge($errors, $entryErrors);
                $errors = array_unique($errors);
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

        foreach ($this->getSavingEntries() as $entry) {
            if (isset($summary[$entry->getRecordDatabaseTableName()])) {
                ++$summary[$entry->getRecordDatabaseTableName()];
                continue;
            }
            $summary[$entry->getRecordDatabaseTableName()] = 1;
        }

        return $summary;
    }

    public function handledRemoteId(string $remoteId): bool
    {
        foreach ($this->logEntries as $entry) {
            if ($entry->getRemoteId() === $remoteId) {
                return true;
            }
        }

        return false;
    }

    public function merge(self $importLog): void
    {
        foreach ($importLog->getEntries() as $entry) {
            $this->addEntry($entry);
        }
    }

    /**
     * @return SavingEntity[]
     */
    private function getSavingEntries(): array
    {
        return array_filter($this->logEntries->getArray(), function (ImportLogEntry $entry) {
            return $entry instanceof SavingEntity;
        });
    }
}
