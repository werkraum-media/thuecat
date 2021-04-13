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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ResolveEntities implements DataProcessorInterface
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var TypoScriptFrontendController
     */
    private $tsfe;

    public function __construct(
        ConnectionPool $connectionPool,
        DataMapper $dataMapper
    ) {
        $this->connectionPool = $connectionPool;
        $this->dataMapper = $dataMapper;
        $this->tsfe = $GLOBALS['TSFE'];
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $as = $cObj->stdWrapValue('as', $processorConfiguration, 'entities');
        $tableName = $cObj->stdWrapValue('table', $processorConfiguration, '');
        $uids = $cObj->stdWrapValue('uids', $processorConfiguration, '');

        $uids = GeneralUtility::intExplode(',', $uids);
        if ($uids === [] || $tableName === '') {
            return $processedData;
        }

        $processedData[$as] = $this->resolveEntities($tableName, $uids);
        return $processedData;
    }

    private function resolveEntities(string $tableName, array $uids): array
    {
        $targetType = '\WerkraumMedia\ThueCat\Domain\Model\Frontend\\' . $this->convertTableToEntity($tableName);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->select('*');
        $queryBuilder->from($tableName);
        $queryBuilder->where($queryBuilder->expr()->in(
            'uid',
            $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
        ));

        $rows = [];
        foreach ($queryBuilder->execute() as $row) {
            $row = $this->tsfe->sys_page->getLanguageOverlay($tableName, $row);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $this->dataMapper->map($targetType, $rows);
    }

    private function convertTableToEntity(string $tableName): string
    {
        $entityPart = str_replace('tx_thuecat_', '', $tableName);
        return GeneralUtility::underscoredToUpperCamelCase($entityPart);
    }
}
