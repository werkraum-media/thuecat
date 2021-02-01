<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Backend;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;

class ImportLogRepository extends Repository
{
    private DataHandler $dataHandler;

    public function __construct(
        ObjectManagerInterface $objectManager,
        DataHandler $dataHandler,
        Typo3QuerySettings $querySettings
    ) {
        parent::__construct($objectManager);

        $this->dataHandler = $dataHandler;
        $this->dataHandler->stripslashes_values = 0;

        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);

        $this->setDefaultOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);
    }

    public function addLog(ImportLog $log): void
    {
        $dataHandler = clone $this->dataHandler;
        $dataHandler->start([
            'tx_thuecat_import_log' => [
                 'NEW0' => [
                    'pid' => 0,
                    'configuration' => $log->getConfiguration()->getUid(),
                ],
            ],
            'tx_thuecat_import_log_entry' => $this->getLogEntries($log),
        ], []);
        $dataHandler->process_datamap();
    }

    private function getLogEntries(ImportLog $log): array
    {
        $number = 1;
        $entries = [];

        foreach ($log->getEntries() as $entry) {
            $number++;

            $entries['NEW' . $number] = [
                'pid' => 0,
                'import_log' => 'NEW0',
                'insertion' => $entry->wasInsertion(),
                'record_uid' => $entry->getRecordUid(),
                'table_name' => $entry->getRecordDatabaseTableName(),
                'errors' => json_encode($entry->getErrors()),
            ];
        }

        return $entries;
    }
}
