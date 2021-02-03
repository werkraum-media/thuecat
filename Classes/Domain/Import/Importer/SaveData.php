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
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

class SaveData
{
    private DataHandler $dataHandler;
    private ConnectionPool $connectionPool;
    private array $errorLog;

    public function __construct(
        DataHandler $dataHandler,
        ConnectionPool $connectionPool
    ) {
        $this->dataHandler = $dataHandler;
        $this->connectionPool = $connectionPool;
    }

    public function import(EntityCollection $entityCollection, ImportLog $log): void
    {
        $this->errorLog = [];

        $this->processSimpleDataHandlerDataMap($entityCollection);
        // TODO: Insert update / insert of localization

        foreach ($entityCollection->getEntities() as $entity) {
            $log->addEntry(new ImportLogEntry($entity, $this->errorLog));
        }
    }

    private function processSimpleDataHandlerDataMap(EntityCollection $collection): void
    {
        $dataArray = [];
        $identifierMapping = [];

        foreach ($collection->getEntities() as $entity) {
            $identifier = $this->getIdentifier($entity);
            if (strpos($identifier, 'NEW') === 0 && $entity->isTranslation()) {
                continue;
            }
            if (is_numeric($identifier)) {
                $entity->setExistingTypo3Uid((int) $identifier);
            } else {
                $identifierMapping[spl_object_id($entity)] = $identifier;
            }

            $dataArray[$entity->getTypo3DatabaseTableName()][$identifier] = $this->getEntityData($entity);
        }

        $dataHandler = clone $this->dataHandler;
        $dataHandler->start($dataArray, []);
        $dataHandler->process_datamap();
        $this->errorLog = array_merge($this->errorLog, $dataHandler->errorLog);

        foreach ($collection->getEntities() as $entity) {
            if (
                isset($identifierMapping[spl_object_id($entity)])
                && isset($dataHandler->substNEWwithIDs[$identifierMapping[spl_object_id($entity)]])
            ) {
                $entity->setImportedTypo3Uid($dataHandler->substNEWwithIDs[$identifierMapping[spl_object_id($entity)]]);
            }
        }
    }

    private function getIdentifier(Entity $entity): string
    {
        $existingUid = $this->getExistingUid($entity);

        if ($existingUid > 0) {
            return (string) $existingUid;
        }

        $identifier = 'NEW_' . sha1($entity->getRemoteId() . $entity->getTypo3SystemLanguageUid());
        // Ensure new ID is max 30, as this is max volumn of the sys_log column
        return substr($identifier, 0, 30);
    }

    private function getEntityData(Entity $entity): array
    {
        return array_merge($entity->getData(), [
            'pid' => $entity->getTypo3StoragePid(),
            'remote_id' => $entity->getRemoteId(),
        ]);
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
