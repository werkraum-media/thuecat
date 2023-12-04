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

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;

class SaveData
{
    /**
     * @var mixed[]
     */
    private array $errorLog;

    public function __construct(
        private readonly DataHandler $dataHandler,
        private readonly ConnectionPool $connectionPool
    ) {
    }

    public function import(EntityCollection $entityCollection, ImportLog $log): void
    {
        $this->errorLog = [];

        $this->updateKnownData($entityCollection);
        $this->createEntities($entityCollection);
        $this->updateKnownData($entityCollection);
        $this->updateEntities($entityCollection);

        foreach ($entityCollection->getEntities() as $entity) {
            $log->addEntry(new SavingEntity($entity, $this->errorLog));
        }
    }

    private function updateKnownData(EntityCollection $entities): void
    {
        foreach ($entities->getEntities() as $entity) {
            if ($entity->exists()) {
                continue;
            }

            $identifier = $this->getIdentifier($entity);
            if (is_numeric($identifier)) {
                $entity->setExistingTypo3Uid((int)$identifier);
            }
        }
    }

    private function createEntities(EntityCollection $entities): void
    {
        $this->createDefaultLanguageEntities($entities);
        $this->createTranslationEntities($entities);
    }

    private function createDefaultLanguageEntities(EntityCollection $entities): void
    {
        $identifierMapping = [];

        $entity = $entities->getDefaultLanguageEntity();
        if ($entity === null) {
            return;
        }
        $identifier = $this->getIdentifier($entity);
        $identifierMapping[spl_object_id($entity)] = $identifier;
        $dataArray[$entity->getTypo3DatabaseTableName()][$identifier] = $this->getEntityData($entity);

        $dataHandler = clone $this->dataHandler;
        $dataHandler->start($dataArray, []);
        $dataHandler->process_datamap();
        $this->errorLog = array_merge($this->errorLog, $dataHandler->errorLog);

        foreach ($entities->getEntities() as $entity) {
            if (
                isset($identifierMapping[spl_object_id($entity)])
                && isset($dataHandler->substNEWwithIDs[$identifierMapping[spl_object_id($entity)]])
            ) {
                $entity->setImportedTypo3Uid($dataHandler->substNEWwithIDs[$identifierMapping[spl_object_id($entity)]]);
            }
        }
    }

    private function createTranslationEntities(EntityCollection $entities): void
    {
        $commandMap = [];

        foreach ($entities->getEntitiesToTranslate() as $entity) {
            $identifier = $this->getDefaultLanguageIdentifier($entity);
            if (
                $entity->isForDefaultLanguage()
                || $identifier === 0
            ) {
                continue;
            }
            $commandMap[$entity->getTypo3DatabaseTableName()][$identifier]['localize'] = $entity->getTypo3SystemLanguageUid();
            $dataHandler = clone $this->dataHandler;
            $dataHandler->start([], $commandMap);
            $dataHandler->process_cmdmap();
            $this->errorLog = array_merge($this->errorLog, $dataHandler->errorLog);
        }
    }

    private function updateEntities(EntityCollection $entities): void
    {
        $dataArray = [];

        foreach ($entities->getExistingEntities() as $entity) {
            $dataArray[$entity->getTypo3DatabaseTableName()][$entity->getTypo3Uid()] = $this->getEntityData($entity);
        }

        $dataHandler = clone $this->dataHandler;
        $dataHandler->start($dataArray, []);
        $dataHandler->process_datamap();
        $this->errorLog = array_merge($this->errorLog, $dataHandler->errorLog);
    }

    private function getIdentifier(Entity $entity): string
    {
        $existingUid = $this->getExistingUid($entity);

        if ($existingUid > 0) {
            return (string)$existingUid;
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
        $tableColumns = $this->connectionPool
            ->getConnectionForTable($entity->getTypo3DatabaseTableName())
            ->getSchemaManager()
            ->listTableColumns($entity->getTypo3DatabaseTableName())
        ;

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($entity->getTypo3DatabaseTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid');
        $queryBuilder->from($entity->getTypo3DatabaseTableName());
        $queryBuilder->where($queryBuilder->expr()->eq(
            'remote_id',
            $queryBuilder->createNamedParameter($entity->getRemoteId())
        ));
        if (isset($tableColumns['sys_language_uid'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                'sys_language_uid',
                $queryBuilder->createNamedParameter($entity->getTypo3SystemLanguageUid())
            ));
        }

        $result = $queryBuilder->executeQuery()->fetchOne();
        if (is_numeric($result)) {
            return (int)$result;
        }

        return 0;
    }

    private function getDefaultLanguageIdentifier(Entity $entity): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($entity->getTypo3DatabaseTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid');
        $queryBuilder->from($entity->getTypo3DatabaseTableName());
        $queryBuilder->where($queryBuilder->expr()->eq(
            'remote_id',
            $queryBuilder->createNamedParameter($entity->getRemoteId())
        ));
        $queryBuilder->andWhere($queryBuilder->expr()->eq(
            'sys_language_uid',
            $queryBuilder->createNamedParameter(0)
        ));

        $result = $queryBuilder->executeQuery()->fetchOne();
        if (is_numeric($result)) {
            return (int)$result;
        }

        return 0;
    }
}
