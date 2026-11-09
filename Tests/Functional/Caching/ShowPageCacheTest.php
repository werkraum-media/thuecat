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
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * The show page carries a cache tag for the record it displays, so an import
 * updating or deleting that record discards the rendered page.
 *
 * On the backend-selection path the tag comes from the configured uid rather
 * than the resolved record: a page rendering the empty state because its pick
 * vanished must still be discarded once that uid exists again.
 */
class ShowPageCacheTest extends AbstractCachingTestCase
{
    private const string TABLE = 'tx_thuecat_tourist_attraction';

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPreselection.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    /**
     * Guard for the tests below: if this fails, the harness cannot observe page
     * caching at all and a green result elsewhere proves nothing.
     */
    #[Test]
    public function theHarnessObservesPageCacheEntries(): void
    {
        $cold = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');
        $this->request(10);
        $warm = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');

        $this->assertSame([], $cold, 'Cache starts cold.');
        $this->assertCount(1, $warm, 'A show request must leave one page-cache entry.');
    }

    #[Test]
    public function theConfiguredRecordTagsThePage(): void
    {
        $this->request(10);

        $this->assertNotSame([], $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_21'), 'The configured record must tag the page it is displayed on.');
    }

    #[Test]
    public function aRecordResolvedFromTheQueryParameterTagsThePage(): void
    {
        // Via detailRequest: the record argument is cacheable, so a request
        // without a valid cHash never reaches the controller.
        $this->executeFrontendSubRequest(
            $this->attractionShowRequest('23', 11)
        );

        $this->assertNotSame([], $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_23'), 'A record shown via the query parameter must tag the page.');
    }

    #[Test]
    public function savingTheConfiguredRecordDiscardsThePage(): void
    {
        $this->request(10);
        $this->assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'));

        $this->saveRecord(21, ['title' => 'Stadtmuseum Erfurt, neu benannt'], self::TABLE);

        $this->assertSame([], $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'), 'Saving the displayed record must discard its page.');
    }

    #[Test]
    public function theNextRequestRendersTheSavedValue(): void
    {
        $this->request(10);
        $this->saveRecord(21, ['title' => 'Stadtmuseum Erfurt, neu benannt'], self::TABLE);

        $body = (string)$this->request(10)->getBody();

        $this->assertStringContainsString('Stadtmuseum Erfurt, neu benannt', $body);
        $this->assertStringNotContainsString('<h2>Stadtmuseum Erfurt</h2>', $body, 'The superseded title must not survive in a cached page.');
    }

    #[Test]
    public function deletingTheConfiguredRecordLeavesNoResidue(): void
    {
        $this->request(13);

        $this->deleteRecord(24, self::TABLE);

        $body = (string)$this->request(13)->getBody();

        $this->assertStringContainsString('Keine Daten vorhanden.', $body);
        $this->assertStringNotContainsString('Ort der verschwindet', $body);
    }

    /**
     * What tagging from the configured uid buys: the empty page holds the tag
     * of a record that does not exist, so recreating it discards the page.
     */
    #[Test]
    public function anEmptyPageIsDiscardedWhenTheConfiguredRecordReturns(): void
    {
        $this->deleteRecord(24, self::TABLE);
        $this->request(13);
        $this->assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_13'), 'The empty page is cached.');

        $this->undeleteRecord(24, self::TABLE);

        $this->assertSame([], $this->cacheIdentifiersTaggedWith('pages', 'pageId_13'), 'A restored record must discard the empty page that waited for it.');
    }

    #[Test]
    public function anEmptyPageCarriesTheConfiguredRecordTag(): void
    {
        $this->deleteRecord(24, self::TABLE);

        $this->request(13);

        $this->assertNotSame([], $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_24'), 'The empty page must stay reachable through the configured uid.');
    }

    #[Test]
    public function theNextRequestRendersAChangedRoomCapacityOfTheVenue(): void
    {
        $this->request(16);
        $this->assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_16'), 'The venue page is cached.');

        $this->saveRecord(1, ['capacity_theatre' => '310'], 'tx_thuecat_meeting_room');

        $rooms = $this->renderedSection(new InternalRequest()->withPageId(16), 'venue', 'meetingRooms');
        $this->assertStringContainsString('310', $rooms);
        $this->assertStringNotContainsString('200', $rooms);
    }

    #[Test]
    public function repeatingARequestReusesItsEntry(): void
    {
        $this->request(10);
        $afterFirst = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');

        $this->request(10);

        $this->assertSame($afterFirst, $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'), 'The same request must not create a second entry.');
    }
}
