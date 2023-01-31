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

namespace WerkraumMedia\ThueCat\Tests\Acceptance;

use WerkraumMedia\ThueCat\Tests\Acceptance\Support\AcceptanceTester;

class BackendConfigurationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/typo3');
        $I->submitForm('#typo3-login-form', [
            'username' => 'admin',
            'p_field' => 'password',
        ]);
        $I->waitForText('New TYPO3 site');
    }

    public function showsIndex(AcceptanceTester $I): void
    {
        $I->click('Configurations');
        $I->switchToContentFrame();
        $I->see('ThüCAT - Configurations');
        $I->see('Example Configuration');
        $I->see('Please provide an import configuration and trigger import to create an organisation.');
    }

    public function allowsToImportExistingConfiguration(AcceptanceTester $I): void
    {
        $I->click('Configurations');
        $I->switchToContentFrame();
        $I->see('Example Configuration');
        $I->see('Never');
        $I->click('Import based on import configuration');

        $I->see('Import finished');
        $I->see('Imported configuration "Example Configuration".');
        $I->see('Tourist-Information Schmalkalden');
    }

    public function showsExecutedImport(AcceptanceTester $I): void
    {
        $I->click('Imports');
        $I->switchToContentFrame();
        $I->see('ThüCAT - Imports');

        $I->see('Example Configuration');
    }
}
