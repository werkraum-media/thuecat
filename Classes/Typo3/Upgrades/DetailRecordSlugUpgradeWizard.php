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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
// TODO: typo3/cms-core:15 Use the EXT:core namespace once v13 support goes.
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKinds;

/**
 * Fills empty slugs of every detail record kind, per row and language.
 *
 * Writes directly instead of through DataHandler: no tstamp change and no
 * cache flush per row. Existing slugs are never touched, so URLs stay stable.
 */
#[UpgradeWizard('thuecat_detailRecordSlugs')]
#[Autoconfigure(public: true)]
final class DetailRecordSlugUpgradeWizard implements UpgradeWizardInterface
{
    public function __construct(
        private readonly DetailRecordKinds $kinds,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function getTitle(): string
    {
        return 'ThueCat: Fill empty slugs of detail records';
    }

    public function getDescription(): string
    {
        return 'Generates the URL segment of every tourist attraction, trail and other detail record that has none yet, from its title. Existing URL segments are kept.';
    }

    public function executeUpdate(): bool
    {
        foreach ($this->slugFields() as [$table, $field, $configuration]) {
            $helper = GeneralUtility::makeInstance(SlugHelper::class, $table, $field, $configuration);
            $eval = $configuration['eval'] ?? '';
            $evalCodes = GeneralUtility::trimExplode(',', is_string($eval) ? $eval : '', true);
            $connection = $this->connectionPool->getConnectionForTable($table);

            foreach ($this->rowsWithEmptySlug($table, $field) as $row) {
                $uid = is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0;
                $pid = is_numeric($row['pid'] ?? null) ? (int)$row['pid'] : 0;
                $slug = $helper->generate($row, $pid);

                // Each row is written before the next is generated, so the
                // uniqueness checks see the slugs of this run.
                $state = RecordStateFactory::forName($table)->fromArray($row, $pid, $uid);
                if (in_array('unique', $evalCodes, true)) {
                    $slug = $helper->buildSlugForUniqueInTable($slug, $state);
                }
                if (in_array('uniqueInSite', $evalCodes, true)) {
                    $slug = $helper->buildSlugForUniqueInSite($slug, $state);
                }
                if (in_array('uniqueInPid', $evalCodes, true)) {
                    $slug = $helper->buildSlugForUniqueInPid($slug, $state);
                }

                $connection->update($table, [$field => $slug], ['uid' => $uid]);
            }
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        foreach ($this->slugFields() as [$table, $field]) {
            if ($this->rowsWithEmptySlug($table, $field) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{0: string, 1: string, 2: array<mixed>}>
     */
    private function slugFields(): array
    {
        $fields = [];
        foreach ($this->kinds->all() as $kind) {
            if (!$this->tcaSchemaFactory->has($kind->table)) {
                continue;
            }
            foreach ($this->tcaSchemaFactory->get($kind->table)->getFieldsOfType(TableColumnType::SLUG) as $field) {
                $fields[] = [$kind->table, $field->getName(), $field->getConfiguration()];
            }
        }

        return $fields;
    }

    /**
     * Hidden rows are included: they get a URL once shown.
     *
     * @return list<array<string, mixed>>
     */
    private function rowsWithEmptySlug(string $table, string $field): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull($field),
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter(''))
                )
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
