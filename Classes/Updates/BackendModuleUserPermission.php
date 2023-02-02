<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class BackendModuleUserPermission implements UpgradeWizardInterface
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function getIdentifier(): string
    {
        return self::class;
    }

    public function getTitle(): string
    {
        return 'Update user permissions for ThüCAT modules.';
    }

    public function getDescription(): string
    {
        return 'The module was migrated to an own group which changes the permission identifiers.';
    }

    public function updateNecessary(): bool
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $qb->getRestrictions()->removeAll();
        $qb->count('*');
        $qb->from('be_users');
        $qb->where($qb->expr()->like('userMods', $qb->createNamedParameter('%site_ThuecatThuecat%')));

        return $qb->execute()->fetchOne() > 0;
    }

    public function executeUpdate(): bool
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $qb->getRestrictions()->removeAll();
        $qb->select('uid', 'userMods');
        $qb->from('be_users');
        $qb->where($qb->expr()->like('userMods', $qb->createNamedParameter('%site_ThuecatThuecat%')));
        $result = $qb->execute();

        foreach ($result as $backendUser) {
            $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
            $qb->update('be_users');
            $qb->set('userMods', $this->updateMods($backendUser['userMods']));
            $qb->where($qb->expr()->eq('uid', $qb->createNamedParameter($backendUser['uid'])));
            $qb->execute();
        }

        return true;
    }

    private function updateMods(string $mods): string
    {
        $mods = GeneralUtility::trimExplode(',', $mods, true);

        unset($mods[array_search('site_ThuecatThuecat', $mods)]);

        $mods[] = 'ThuecatThuecat';
        $mods[] = 'ThuecatThuecat_ThuecatConfigurations';
        $mods[] = 'ThuecatThuecat_ThuecatImports';

        return implode(',', $mods);
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][self::class] = self::class;
    }
}
