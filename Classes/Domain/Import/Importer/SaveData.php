<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

class SaveData
{
    private DataHandler $dataHandler;
    private ConnectionPool $connectionPool;

    public function __construct(
        DataHandler $dataHandler,
        ConnectionPool $connectionPool
    ) {
        $this->dataHandler = $dataHandler;
        $this->dataHandler->stripslashes_values = 0;
        $this->connectionPool = $connectionPool;
    }

    public function import(Entity $entity, ImportLog $log): void
    {
        $dataHandler = clone $this->dataHandler;

        $identifier = $this->getIdentifier($entity);
        $dataHandler->start([
            $entity->getTypo3DatabaseTableName() => [
                 $identifier => array_merge($entity->getData(), [
                    'pid' => $entity->getTypo3StoragePid(),
                    'remote_id' => $entity->getRemoteId(),
                ]),
            ],
        ], []);
        $dataHandler->process_datamap();

        if (isset($dataHandler->substNEWwithIDs[$identifier])) {
            $entity->setImportedTypo3Uid($dataHandler->substNEWwithIDs[$identifier]);
        } elseif (is_numeric($identifier)) {
            $entity->setExistingTypo3Uid((int) $identifier);
        }

        $log->addEntry(new ImportLogEntry(
            $entity,
            $dataHandler->errorLog
        ));
    }

    private function getIdentifier(Entity $entity): string
    {
        $existingUid = $this->getExistingUid($entity);

        if ($existingUid > 0) {
            return (string) $existingUid;
        }

        return 'NEW_1';
    }

    private function getExistingUid(Entity $entity): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($entity->getTypo3DatabaseTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid');
        $queryBuilder->from($entity->getTypo3DatabaseTableName());
        $queryBuilder->where($queryBuilder->expr()->eq(
            'remote_id',
            $queryBuilder->createNamedParameter($entity->getRemoteId())
        ));

        $result = $queryBuilder->execute()->fetchColumn();
        if (is_numeric($result)) {
            return (int) $result;
        }

        return 0;
    }
}
