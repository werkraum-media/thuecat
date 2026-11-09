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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

/**
 * Page 10 ticks no record type, page 20 only "meeting venue", page 30 both.
 */
class TouristAttractionRecordTypeListTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForRecordTypes.php';
    }

    #[Test]
    public function listsOnlyMeetingVenuesWhenOnlyThatTypeIsTicked(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(20));

        $this->assertStringContainsString('Tagungshotel am Dom', $body);
        $this->assertStringNotContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function listsEveryTypeWhenNoneIsTicked(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10));

        $this->assertStringContainsString('Tagungshotel am Dom', $body);
        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function listsEveryTickedType(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(30));

        $this->assertStringContainsString('Tagungshotel am Dom', $body);
        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function requestParametersCannotWidenTheTickedTypes(): void
    {
        $request = new InternalRequest()
            ->withPageId(20)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['recordTypes' => 'attraction,meeting_venue']]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Tagungshotel am Dom', $body);
        $this->assertStringNotContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function requestParametersCannotNarrowAnUntickedList(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['recordTypes' => 'meeting_venue']]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function listItemsRenderVenueOutputForMeetingVenuesOnly(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10));

        $this->assertSame(1, substr_count($body, 'data-venue="listItem"'));
        $this->assertStringContainsString('250', $this->renderedSection($this->pageRequest(10), 'venue', 'listItem'));
    }
}
