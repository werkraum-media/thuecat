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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as Typo3AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryMatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryUnmatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;

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

    /**
     * @return list<string>
     */
    public function getListOfErrors(): array
    {
        $errors = [];

        foreach ($this->getEntries() as $entry) {
            if ($entry->isError()) {
                $errors[] = 'Resource: ' . $entry->getRemoteId() . ' Error: ' . $entry->getMessage();
            }
        }

        return array_values(array_unique($errors));
    }

    public function hasErrors(): bool
    {
        foreach ($this->getEntries() as $entry) {
            if ($entry->isError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persisted-record entries only, so the total matches the per-table summary.
     */
    public function getCountOfSavingEntries(): int
    {
        return count($this->getSavingEntries());
    }

    /**
     * Per-table counts, split into inserted (new) and updated records.
     *
     * @return array<string, array{total: int, inserted: int, updated: int}>
     */
    public function getSummaryOfEntries(): array
    {
        $summary = [];

        foreach ($this->getSavingEntries() as $entry) {
            $table = $entry->getRecordDatabaseTableName();
            $summary[$table] ??= ['total' => 0, 'inserted' => 0, 'updated' => 0];
            ++$summary[$table]['total'];
            ++$summary[$table][$entry->wasInsertion() ? 'inserted' : 'updated'];
        }

        return $summary;
    }

    /**
     * Matched types → current title (read live so renames show through), sorted.
     *
     * @return array<string, string>
     */
    public function getMatchedCategories(): array
    {
        $matched = [];
        foreach ($this->getEntries() as $entry) {
            if ($entry instanceof CategoryMatched) {
                $matched[$entry->getRemoteId()] = $this->categoryTitle($entry->getRecordUid());
            }
        }
        ksort($matched);
        return $matched;
    }

    /**
     * Unmatched category types for the report, alphabetically.
     *
     * @return list<string>
     */
    public function getUnmatchedCategories(): array
    {
        $unmatched = [];
        foreach ($this->getEntries() as $entry) {
            if ($entry instanceof CategoryUnmatched) {
                $unmatched[] = $entry->getRemoteId();
            }
        }
        sort($unmatched);
        return $unmatched;
    }

    protected function categoryTitle(int $uid): string
    {
        if ($uid <= 0) {
            return '';
        }

        // Entity is not DI-managed, so pull the repo on demand.
        return GeneralUtility::makeInstance(SysCategoryRepository::class)->findTitle($uid);
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
    protected function getSavingEntries(): array
    {
        return array_filter($this->logEntries->getArray(), function (ImportLogEntry $entry) {
            return $entry instanceof SavingEntity;
        });
    }
}
