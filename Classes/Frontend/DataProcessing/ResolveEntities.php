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

namespace WerkraumMedia\ThueCat\Frontend\DataProcessing;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ResolveEntities implements DataProcessorInterface
{
    private readonly TypoScriptFrontendController $tsfe;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly DataMapper $dataMapper
    ) {
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

        $as = (string)$cObj->stdWrapValue('as', $processorConfiguration, 'entities');
        $tableName = (string)$cObj->stdWrapValue('table', $processorConfiguration, '');
        $uids = (string)$cObj->stdWrapValue('uids', $processorConfiguration, '');

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
        foreach ($queryBuilder->executeQuery()->iterateAssociative() as $row) {
            // TODO: typo3/cms-core:14.0 Remove this condition, should always be an instance now.
            if (!$this->tsfe->sys_page instanceof PageRepository) {
                continue;
            }

            $row = $this->tsfe->sys_page->getLanguageOverlay($tableName, $row);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        usort($rows, function (array $rowA, array $rowB) use ($uids) {
            return array_search($rowA['uid'], $uids) <=> array_search($rowB['uid'], $uids);
        });

        return $this->dataMapper->map($targetType, $rows);
    }

    private function convertTableToEntity(string $tableName): string
    {
        $entityPart = str_replace('tx_thuecat_', '', $tableName);
        return GeneralUtility::underscoredToUpperCamelCase($entityPart);
    }
}
