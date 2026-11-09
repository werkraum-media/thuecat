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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Plugin 20 (page 20) lists meeting venues only.
 */
final class RecordTypeListCacheTest extends AbstractCachingTestCase
{
    private const string EVERY_TYPE_FLEXFORM = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.recordTypes">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForRecordTypes.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    #[Test]
    public function theNextRequestFollowsTheChangedRecordTypes(): void
    {
        $this->assertStringNotContainsString('Stadtmuseum Erfurt', (string)$this->request(20)->getBody());

        $this->saveRecord(20, ['pi_flexform' => self::EVERY_TYPE_FLEXFORM], 'tt_content');

        $body = (string)$this->request(20)->getBody();
        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
        $this->assertStringContainsString('Tagungshotel am Dom', $body);
    }
}
