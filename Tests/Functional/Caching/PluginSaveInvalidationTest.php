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

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Saving a list plugin discards the lists it stored, whatever FlexForm value
 * changed, and leaves the lists of every other plugin in place.
 *
 * Plugins 10 (page 10) and 20 (page 20) both list storage folder 11.
 */
final class PluginSaveInvalidationTest extends AbstractCachingTestCase
{
    protected const BACKEND_ORDER_FLEXFORM = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.sortBy">
                    <value index="vDEF">sorting</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSorting.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    #[Test]
    public function savingAPluginDiscardsItsListsAndKeepsOthers(): void
    {
        $this->request(10);
        $pluginTen = $this->identifiersForRecords(Extension::CACHE_LIST, [1]);
        $this->request(20);
        $both = $this->identifiersForRecords(Extension::CACHE_LIST, [1]);
        self::assertCount(1, $pluginTen, 'Plugin 10 stored its list.');
        self::assertCount(2, $both, 'Plugin 20 stored its list.');

        $this->saveRecord(10, ['pi_flexform' => self::BACKEND_ORDER_FLEXFORM], 'tt_content');

        self::assertSame(
            array_values(array_diff($both, $pluginTen)),
            $this->identifiersForRecords(Extension::CACHE_LIST, [1]),
            'Only the saved plugin\'s list is discarded.'
        );
    }

    #[Test]
    public function theNextRequestFollowsTheNewSortSetting(): void
    {
        $this->request(10);

        $this->saveRecord(10, ['pi_flexform' => self::BACKEND_ORDER_FLEXFORM], 'tt_content');

        $body = (string)$this->request(10)->getBody();
        self::assertLessThan(mb_strpos($body, 'Gamma Garten'), mb_strpos($body, 'Beta Park'));
        self::assertLessThan(mb_strpos($body, 'Alpha Museum'), mb_strpos($body, 'Gamma Garten'));
    }
}
