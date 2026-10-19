<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
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

namespace WerkraumMedia\ThueCat\Import\SyncChannel;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;

/**
 * Computes the `since` that both lists of a sync channel run are requested with.
 */
readonly class SyncWindow
{
    protected const EVENT_TABLE = 'tx_events_domain_model_event';

    public function __construct(
        protected ImportLogRepository $importLogRepository,
        protected ConnectionPool $connectionPool,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected ClockInterface $clock,
    ) {
    }

    public function since(int $configurationUid, int $storagePid): DateTimeImmutable
    {
        // A fixed zone keeps `since` independent of the server's setting.
        $now = $this->clock->now()->setTimezone(new DateTimeZone('Europe/Berlin'));
        // The API does not say how far `removed` reaches back.
        $fullWindow = $now->modify('-5 years');

        if (!$this->holdsEvent($storagePid)) {
            return $fullWindow;
        }

        $anchor = $this->importLogRepository->findLatestSuccessfulCreation($configurationUid);
        if ($anchor === null) {
            return $fullWindow;
        }

        // The overlap absorbs clock skew and changes indexed after the run started.
        return $anchor->setTimezone($now->getTimezone())->modify('-1 day');
    }

    protected function holdsEvent(int $storagePid): bool
    {
        $languageField = $this->tcaSchemaFactory
            ->get(self::EVENT_TABLE)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::EVENT_TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $count = $queryBuilder
            ->count('uid')
            ->from(self::EVENT_TABLE)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) && (int)$count > 0;
    }
}
