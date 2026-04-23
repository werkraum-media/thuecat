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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\StringUtility;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;

#[Autoconfigure(public: true)]
class Resolver
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Rewrites the payload in place so each row's outer key becomes either
     * the existing DB uid (as string) or a StringUtility::getUniqueId('NEW')
     * placeholder. Always injects `pid`. After this call the payload can be
     * handed to DataHandler once the transients bucket is empty.
     */
    public function resolve(DataHandlerPayload $payload, int $storagePid): void
    {
        foreach ($payload->getPayload() as $table => $rows) {
            foreach (array_keys($rows) as $remoteId) {
                // Parser always registers rows under their remote-id URL, so this
                // is a string at first pass. After we rekey below, PHP re-casts
                // numeric keys to int, but those rows are already resolved and
                // won't re-enter this loop.
                $remoteId = (string)$remoteId;
                $uid = $this->findUidByRemoteId($table, $remoteId);
                $newKey = $uid > 0 ? (string)$uid : StringUtility::getUniqueId('NEW');

                $payload->rekeyRow($table, $remoteId, $newKey);
                $payload->setField($table, $newKey, 'pid', $storagePid);
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
