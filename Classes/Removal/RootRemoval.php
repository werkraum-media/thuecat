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
 * Removes one root and the relations it leaves unused, each by its own DataHandler run, with the rights of the
 * user who started the job.
 *
 * Public, so functional tests can put a failing removal in its place.
 */
#[Autoconfigure(public: true)]
class RootRemoval
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly RelationUsage $relationUsage,
    ) {
    }

    public function remove(RemovalJob $job, RootRecord $root): RootOutcome
    {
        if (!$this->exists($root->table, $root->uid)) {
            return new RootOutcome(false, true);
        }

        $backendUser = $this->backendUserOf($job);
        $candidates = $this->relationUsage->candidatesOf($root->table, $root->uid);

        // Translations, inline children and file references follow the root.
        $errors = $this->delete($root->table, $root->uid, $backendUser);
        if ($errors !== []) {
            return new RootOutcome(false, false, $errors);
        }

        $relations = $this->removeUnused($candidates, $backendUser);

        return new RootOutcome(true, false, $relations->errors, $relations->removed);
    }

    /**
     * A removed relation's own relations are judged next; a removed record is no candidate any more, so the walk ends.
     *
     * @param list<RelationRecord> $candidates
     */
    protected function removeUnused(array $candidates, BackendUserAuthentication $backendUser): RelationsOutcome
    {
        $removed = 0;
        $errors = [];
        foreach ($candidates as $candidate) {
            if (!$this->exists($candidate->table, $candidate->uid) || !$this->relationUsage->isUnused($candidate)) {
                continue;
            }

            $further = $this->relationUsage->candidatesOf($candidate->table, $candidate->uid);
            $deleteErrors = $this->delete($candidate->table, $candidate->uid, $backendUser);
            if ($deleteErrors !== []) {
                foreach ($deleteErrors as $error) {
                    $errors[] = $candidate->table . ':' . $candidate->uid . ' ' . $error;
                }

                continue;
            }

            $furtherOutcome = $this->removeUnused($further, $backendUser);
            $removed += 1 + $furtherOutcome->removed;
            $errors = [...$errors, ...$furtherOutcome->errors];
        }

        return new RelationsOutcome($removed, $errors);
    }

    /**
     * @return list<string> the errors DataHandler logged
     */
    protected function delete(string $table, int $uid, BackendUserAuthentication $backendUser): array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$table => [$uid => ['delete' => 1]]], $backendUser);
        $dataHandler->process_cmdmap();

        /** @var list<string> $errors */
        $errors = $dataHandler->errorLog;

        return $errors;
    }

    protected function exists(string $table, int $uid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $count = $queryBuilder
            ->count('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
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
