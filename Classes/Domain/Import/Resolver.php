<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Domain\Import;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;

#[Autoconfigure(public: true)]
class Resolver
{
    /**
     * Hard-coded mapping from transient bucket name to
     * [target table, target relation field on the owning row].
     * Extend as new buckets get ref→uid resolution.
     */
    private const BUCKET_MAP = [
        'managedBy' => ['tx_thuecat_organisation', 'managed_by'],
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Rewrites the payload in place so each row's outer key becomes either
     * the existing DB uid (as string) or a StringUtility::getUniqueId('NEW')
     * placeholder, injects `pid`, and drains the transient buckets against
     * the DB. Buckets that need a live API fetch are NOT handled here yet.
     */
    public function resolve(DataHandlerPayload $payload, int $storagePid): void
    {
        // remote_id → current outer key (uid string or NEW… placeholder).
        // Populated as rows get rekeyed; used to locate the owning row for
        // a transient write without re-probing the DB.
        $remoteIdToKey = [];

        $this->rekeyRowsAndInjectPid($payload, $storagePid, $remoteIdToKey);
        $this->drainTransients($payload, $remoteIdToKey);
    }

    /**
     * @param array<string, string> $remoteIdToKey
     */
    private function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        int $storagePid,
        array &$remoteIdToKey
    ): void {
        foreach ($payload->getPayload() as $table => $rows) {
            foreach (array_keys($rows) as $remoteId) {
                $remoteId = (string)$remoteId;
                $uid = $this->findUidByRemoteId($table, $remoteId);
                $newKey = $uid > 0 ? (string)$uid : StringUtility::getUniqueId('NEW');

                $payload->rekeyRow($table, $remoteId, $newKey);
                $payload->setField($table, $newKey, 'pid', $storagePid);
                $remoteIdToKey[$remoteId] = $newKey;
            }
        }
    }

    /**
     * Outer loop over remaining transients. Each pass must drop at least
     * one @id, otherwise we'd spin forever — throw instead.
     *
     * @param array<string, string> $remoteIdToKey
     */
    private function drainTransients(DataHandlerPayload $payload, array &$remoteIdToKey): void
    {
        while ($payload->getTransients() !== []) {
            $progress = false;

            foreach ($payload->getTransients() as $ownerTable => $rowsByRemoteId) {
                foreach ($rowsByRemoteId as $ownerRemoteId => $buckets) {
                    foreach ($buckets as $bucket => $references) {
                        if (!isset(self::BUCKET_MAP[$bucket])) {
                            // Unknown bucket (e.g. accessibilitySpecification,
                            // media) — not in scope for this pass. Leave it so
                            // a later resolver stage can pick it up. We still
                            // need progress elsewhere or the guard will fire.
                            continue;
                        }

                        [$targetTable, $targetField] = self::BUCKET_MAP[$bucket];
                        $ownerKey = $remoteIdToKey[$ownerRemoteId] ?? null;
                        if ($ownerKey === null) {
                            continue;
                        }

                        foreach ($references as $reference) {
                            $uid = $this->findUidByRemoteId($targetTable, $reference);
                            if ($uid <= 0) {
                                // Would need an API fetch. Not in this pass.
                                continue;
                            }

                            $payload->setRelationField($ownerTable, $ownerKey, $targetField, $uid);
                            $payload->removeTransient($ownerTable, $ownerRemoteId, $bucket, $reference);
                            $remoteIdToKey[$reference] = (string)$uid;
                            $progress = true;
                        }
                    }
                }
            }

            if (!$progress) {
                throw new RuntimeException(
                    'Resolver made no progress draining transients; remaining: '
                    . json_encode($payload->getTransients()),
                    1745000000
                );
            }
        }
    }

    private function findUidByRemoteId(string $table, string $remoteId): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $queryBuilder->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                'remote_id',
                $queryBuilder->createNamedParameter($remoteId)
            ))
        ;

        $result = $queryBuilder->executeQuery()->fetchOne();
        return is_numeric($result) ? (int)$result : 0;
    }
}
