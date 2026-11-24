<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Backend;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase as TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

final class ImportConfigurationTest extends TestCase
{
    #[Test]
    public function returnsTitle(): void
    {
        $subject = new ImportConfiguration();
        $subject->_setProperty('title', 'Example Title');

        $this->assertSame('Example Title', $subject->getTitle());
    }

    #[Test]
    public function returnsType(): void
    {
        $subject = new ImportConfiguration();
        $subject->_setProperty('type', 'static');

        $this->assertSame('static', $subject->getType());
    }

    #[Test]
    public function returnsTableName(): void
    {
        $subject = new ImportConfiguration();

        $this->assertSame('tx_thuecat_import_configuration', $subject->getTableName());
    }

    #[Test]
    public function returnsLastChanged(): void
    {
        $lastChanged = new DateTimeImmutable();

        $subject = new ImportConfiguration();

        $subject->_setProperty('tstamp', $lastChanged);

        $this->assertSame($lastChanged, $subject->getLastChanged());
    }

    #[Test]
    public function returnsTheWidgetsHostAsSyncApiDomain(): void
    {
        $subject = new ImportConfiguration();

        $this->assertSame('https://widgets.thuecat.org', $subject->getSyncApiDomain());
    }
}
