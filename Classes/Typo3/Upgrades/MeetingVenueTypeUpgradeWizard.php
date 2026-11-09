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

namespace WerkraumMedia\ThueCat\Typo3\Upgrades;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Types the stored attractions that are meeting venues, judged by their meeting type
 * categories, together with their translations. The hotel category decides over the
 * general meeting category. Attractions without a meeting type category are not touched.
 *
 * Every row is typed, deleted ones included, so a restored venue keeps its type.
 */
#[UpgradeWizard('thuecat_meetingVenueType')]
#[Autoconfigure(public: true)]
class MeetingVenueTypeUpgradeWizard implements UpgradeWizardInterface
{
    protected const TABLE = 'tx_thuecat_tourist_attraction';

    protected const VENUE_TYPE_BY_CATEGORY = [
        'type:thuecat:MeetingCongressHotel' => 'meeting_congress_hotel',
        'type:thuecat:MeetingCongress' => 'meeting_congress',
    ];

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    public function getTitle(): string
    {
        return 'ThueCat: Type stored meeting venues';
    }

    public function getDescription(): string
    {
        return 'Gives tourist attractions related to the conference hotel or meeting and congress category the record type meeting venue, translations included.';
    }

    public function executeUpdate(): bool
    {
        $parentField = $this->languageParentField();

        foreach ($this->venueTypeByRecord() as $uid => $venueType) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->update(self::TABLE)
                ->set('type', 'meeting_venue')
                ->set('venue_type', $venueType)
                ->where($queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq($parentField, $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
                ))
                ->executeStatement()
            ;
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $uids = array_keys($this->venueTypeByRecord());
        if ($uids === []) {
            return false;
        }

        $parentField = $this->languageParentField();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $untyped = $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER)),
                    $queryBuilder->expr()->in($parentField, $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER))
                ),
                $queryBuilder->expr()->neq('type', $queryBuilder->createNamedParameter('meeting_venue'))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($untyped) && (int)$untyped > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * A category related to a translation types its default-language record.
     *
     * @return array<int, string> uid of the default-language record => venue type
     */
    protected function venueTypeByRecord(): array
    {
        $parentField = $this->languageParentField();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('record.uid', 'record.' . $parentField . ' AS parent', 'category.remote_id')
            ->from('sys_category_record_mm', 'mm')
            ->join('mm', 'sys_category', 'category', $queryBuilder->expr()->eq('category.uid', $queryBuilder->quoteIdentifier('mm.uid_local')))
            ->join('mm', self::TABLE, 'record', $queryBuilder->expr()->eq('record.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign')))
            ->where(
                $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter(self::TABLE)),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories')),
                $queryBuilder->expr()->eq('category.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'category.remote_id',
                    $queryBuilder->createNamedParameter(array_keys(self::VENUE_TYPE_BY_CATEGORY), ArrayParameterType::STRING)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $venueTypes = [];
        foreach ($rows as $row) {
            $parent = is_numeric($row['parent'] ?? null) ? (int)$row['parent'] : 0;
            $uid = $parent > 0 ? $parent : (is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0);
            $remoteId = is_string($row['remote_id'] ?? null) ? $row['remote_id'] : '';
            $venueType = self::VENUE_TYPE_BY_CATEGORY[$remoteId] ?? null;
            if ($uid === 0 || $venueType === null) {
                continue;
            }

            if (($venueTypes[$uid] ?? null) !== 'meeting_congress_hotel') {
                $venueTypes[$uid] = $venueType;
            }
        }

        return $venueTypes;
    }

    protected function languageParentField(): string
    {
        return $this->tcaSchemaFactory
            ->get(self::TABLE)
            ->getCapability(TcaSchemaCapability::Language)
            ->getTranslationOriginPointerField()
            ->getName()
        ;
    }
}
