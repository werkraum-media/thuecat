<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Frontend\DataProcessing;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class ResolveEntities implements DataProcessorInterface
{
    private ConnectionPool $connectionPool;
    private DataMapper $dataMapper;

    public function __construct(
        ConnectionPool $connectionPool,
        DataMapper $dataMapper
    ) {
        $this->connectionPool = $connectionPool;
        $this->dataMapper = $dataMapper;
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $as = $cObj->stdWrapValue('as', $processorConfiguration, 'entities');
        $table = $cObj->stdWrapValue('table', $processorConfiguration, '');
        $uids = $cObj->stdWrapValue('uids', $processorConfiguration, '');

        $uids = GeneralUtility::intExplode(',', $uids);
        if ($uids === [] || $table === '') {
            return $processedData;
        }

        $processedData[$as] = $this->resolveEntities($table, $uids);
        return $processedData;
    }

    private function resolveEntities(string $table, array $uids): array
    {
        $targetType = '\WerkraumMedia\ThueCat\Domain\Model\Frontend\\' . $this->convertTableToEntity($table);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->select('*');
        $queryBuilder->from($table);
        $queryBuilder->where($queryBuilder->expr()->in(
            'uid',
            $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
        ));

        return $this->dataMapper->map($targetType, $queryBuilder->execute()->fetchAll());
    }

    private function convertTableToEntity(string $table): string
    {
        $entityPart = str_replace('tx_thuecat_', '', $table);
        return GeneralUtility::underscoredToUpperCamelCase($entityPart);
    }
}
