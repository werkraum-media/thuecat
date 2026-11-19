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

namespace WerkraumMedia\ThueCat\Removal;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Removes one root, with the rights of the user who started the job.
 *
 * Public, so functional tests can put a failing removal in its place.
 */
#[Autoconfigure(public: true)]
class RootRemoval
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    public function remove(RemovalJob $job, RootRecord $root): RootOutcome
    {
        if (!$this->exists($root)) {
            return new RootOutcome(false, true);
        }

        // Translations, inline children and file references follow the root.
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$root->table => [$root->uid => ['delete' => 1]]], $this->backendUserOf($job));
        $dataHandler->process_cmdmap();

        /** @var list<string> $errors */
        $errors = $dataHandler->errorLog;
        if ($errors !== []) {
            return new RootOutcome(false, false, $errors);
        }

        return new RootOutcome(true, false);
    }

    protected function exists(RootRecord $root): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($root->table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $count = $queryBuilder
            ->count('uid')
            ->from($root->table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($root->uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) && (int)$count > 0;
    }

    /**
     * The worker runs as _cli_, an admin; the job's own user brings back the group's permissions.
     */
    protected function backendUserOf(RemovalJob $job): BackendUserAuthentication
    {
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->setBeUserByUid($job->backendUser);
        $backendUser->fetchGroupData();

        return $backendUser;
    }
}
