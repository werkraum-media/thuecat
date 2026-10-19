<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Domain\Repository\Backend;

use DateTimeImmutable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ImportLogRepository extends Repository
{
    // Run-wide failures only. Faults of single records must not reset the
    // anchor, or one bad event would re-fetch the whole history.
    protected const DISQUALIFYING_ENTRY_TYPES = [
        'runAborted',
        'runFailed',
        'fetchingError',
    ];

    public function __construct(
        Typo3QuerySettings $querySettings,
        protected readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();

        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);

        $this->setDefaultOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);
    }

    public function findLatestSuccessfulCreation(int $configurationUid): ?DateTimeImmutable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_log');
        $crdate = $queryBuilder
            ->select('log.crdate')
            ->from('tx_thuecat_import_log', 'log')
            ->leftJoin(
                'log',
                'tx_thuecat_import_log_entry',
                'entry',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('entry.import_log', $queryBuilder->quoteIdentifier('log.uid')),
                    $queryBuilder->expr()->in(
                        'entry.type',
                        $queryBuilder->createNamedParameter(self::DISQUALIFYING_ENTRY_TYPES, Connection::PARAM_STR_ARRAY)
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'log.configuration',
                    $queryBuilder->createNamedParameter($configurationUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->isNull('entry.uid')
            )
            ->orderBy('log.crdate', 'DESC')
            ->addOrderBy('log.uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne()
        ;

        if (!is_numeric($crdate)) {
            return null;
        }

        return new DateTimeImmutable('@' . $crdate);
    }
}
