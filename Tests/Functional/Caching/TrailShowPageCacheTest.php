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
 * The trail show page carries a cache tag for the record it displays, so an
 * import updating or deleting that record discards the rendered page.
 */
final class TrailShowPageCacheTest extends AbstractCachingTestCase
{
    private const string TABLE = 'tx_thuecat_trail';

    protected function getDataSetFileName(): string
    {
        return 'TrailsForPreselection.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    #[Test]
    public function theConfiguredRecordTagsThePage(): void
    {
        $this->request(10);

        $this->assertNotSame([], $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_21'), 'The configured record must tag the page it is displayed on.');
    }

    #[Test]
    public function savingTheConfiguredRecordDiscardsThePage(): void
    {
        $this->request(10);
        $this->assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'));

        $this->saveRecord(21, ['title' => 'Goethe-Erlebnisweg, neu benannt'], self::TABLE);

        $this->assertSame([], $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'), 'Saving the displayed record must discard its page.');
    }

    #[Test]
    public function theNextRequestRendersTheSavedValue(): void
    {
        $this->request(10);
        $this->saveRecord(21, ['title' => 'Goethe-Erlebnisweg, neu benannt'], self::TABLE);

        $body = (string)$this->request(10)->getBody();

        $this->assertStringContainsString('Goethe-Erlebnisweg, neu benannt', $body);
        $this->assertStringNotContainsString('<h2>Goethe-Erlebnisweg</h2>', $body, 'The superseded title must not survive in a cached page.');
    }

    #[Test]
    public function anEmptyPageCarriesTheConfiguredRecordTag(): void
    {
        $this->deleteRecord(24, self::TABLE);

        $this->request(13);

        $this->assertNotSame([], $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_24'), 'The empty page must stay reachable through the configured uid.');
    }
}
