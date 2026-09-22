<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;

/**
 * Relates imported events to the ThueCat places they happen at.
 */
class EventPlaceMatcher
{
    public const FIELD_HOSTS = 'hosts_events';
    public const FIELD_MANAGES = 'manages_event';

    /**
     * Place table => the relation fields it carries. Only organisations
     * manage events; every place can host them.
     */
    protected const PLACE_TABLES = [
        'tx_thuecat_tourist_attraction' => [self::FIELD_HOSTS],
        'tx_thuecat_town' => [self::FIELD_HOSTS],
        'tx_thuecat_organisation' => [self::FIELD_HOSTS, self::FIELD_MANAGES],
        'tx_thuecat_tourist_information' => [self::FIELD_HOSTS],
    ];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * The place a reference names, or null when no record in the site carries
     * that remote_id.
     *
     * @param list<int> $sitePageIds
     *
     * @return array{table: string, uid: int}|null
     */
    public function findPlaceByRemoteId(string $remoteId, array $sitePageIds, string $field): ?array
    {
        if ($remoteId === '') {
            return null;
        }

        foreach (self::PLACE_TABLES as $table => $fields) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $uid = $this->findUidByRemoteId($table, $remoteId, $sitePageIds);
            if ($uid > 0) {
                return ['table' => $table, 'uid' => $uid];
            }
        }

        return null;
    }

    /**
     * The place whose normalized title and postal code equal the inline
     * venue's.
     * More than one candidate is no match: a wrong relation puts an event on
     * the wrong place's page.
     *
     * @param list<int> $sitePageIds
     *
     * @return array{table: string, uid: int}|null
     */
    public function findPlaceByNameAndPostalCode(
        string $name,
        string $postalCode,
        array $sitePageIds,
        string $field
    ): ?array {
        $candidates = $this->findCandidatesByNameAndPostalCode($name, $postalCode, $sitePageIds, $field);

        if (count($candidates) !== 1) {
            return null;
        }

        return $candidates[0];
    }

    /**
     * Every place the rule reaches. The caller decides what more than one
     * means; the log needs all of them to name an ambiguity.
     *
     * @param list<int> $sitePageIds
     *
     * @return list<array{table: string, uid: int}>
     */
    public function findCandidatesByNameAndPostalCode(
        string $name,
        string $postalCode,
        array $sitePageIds,
        string $field
    ): array {
        $normalized = $this->normalize($name);
        if ($normalized === '' || $postalCode === '') {
            return [];
        }

        $candidates = [];
        foreach (self::PLACE_TABLES as $table => $fields) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            foreach ($this->findUidsByPostalCode($table, $postalCode, $sitePageIds) as $uid => $title) {
                if ($this->normalize($title) === $normalized) {
                    $candidates[] = ['table' => $table, 'uid' => $uid];
                }
            }
        }

        return $candidates;
    }

    /**
     * Both sides of a comparison pass through this, so a difference in case,
     * umlaut spelling, street-name abbreviation or punctuation does not
     * prevent a match.
     */
    public function normalize(string $value): string
    {
        $value = mb_strtolower($value);

        $value = strtr($value, [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
        ]);

        $value = (string)preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim($value);

        $value = (string)preg_replace('/(\w)strasse\b/', '$1str', $value);
        $value = (string)preg_replace('/\bstrasse\b/', 'str', $value);

        return $value;
    }

    /**
     * Stage each collected match as an update on the place row, merged with
     * what the column already holds.
     */
    public function flush(DataHandlerPayload $payload, ResolverContext $context): void
    {
        foreach ($context->collectedPlaceMatches as $match) {
            $eventUid = $context->remoteIdToKey[$match->eventRemoteId] ?? '';
            if (!is_numeric($eventUid) || (int)$eventUid <= 0) {
                continue;
            }

            $this->relate($payload, $match->placeTable, $match->placeUid, $match->field, (int)$eventUid);
        }
    }

    protected function relate(
        DataHandlerPayload $payload,
        string $table,
        int $placeUid,
        string $field,
        int $eventUid
    ): void {
        $key = (string)$placeUid;

        $existing = $payload->getDataMap()[$table][$key][$field] ?? null;
        if (!is_string($existing)) {
            $existing = $this->readRelation($table, $placeUid, $field);
        }

        $uids = $existing === '' ? [] : explode(',', $existing);
        if (in_array((string)$eventUid, $uids, true)) {
            return;
        }

        $uids[] = (string)$eventUid;

        // An organisation can be related in both roles in one run, so the row
        // is extended rather than replaced.
        $row = $payload->getDataMap()[$table][$key] ?? [];
        $row[$field] = implode(',', $uids);
        $payload->addRow($table, $key, $row);
    }

    /**
     * What the place already lists
     */
    protected function readRelation(string $table, int $uid, string $field): string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $value = $queryBuilder
            ->select($field)
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_string($value) ? $value : '';
    }

    /**
     * Places in the site whose address carries this postal code
     *
     * @param list<int> $sitePageIds
     *
     * @return array<int, string>
     */
    protected function findUidsByPostalCode(string $table, string $postalCode, array $sitePageIds): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $queryBuilder
            ->select($table . '.uid', $table . '.title')
            ->from($table)
            ->innerJoin(
                $table,
                'tx_thuecat_address',
                'address',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('address.parentid', $queryBuilder->quoteIdentifier($table . '.uid')),
                    $queryBuilder->expr()->eq(
                        'address.parenttable',
                        $queryBuilder->createNamedParameter($table)
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'address.zip',
                    $queryBuilder->createNamedParameter($postalCode)
                ),
                $queryBuilder->expr()->eq(
                    'address.deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $table . '.sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
        ;

        if ($sitePageIds !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                $table . '.pid',
                $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
            ));
        }

        $places = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            if (is_numeric($row['uid'] ?? null) && is_string($row['title'] ?? null)) {
                $places[(int)$row['uid']] = $row['title'];
            }
        }

        return $places;
    }

    /**
     * @param list<int> $sitePageIds
     */
    protected function findUidByRemoteId(string $table, string $remoteId, array $sitePageIds): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
        ;

        // A place in another site is not in scope to be related to
        if ($sitePageIds !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
            ));
        }

        $uid = $queryBuilder->executeQuery()->fetchOne();

        return is_numeric($uid) ? (int)$uid : 0;
    }
}
