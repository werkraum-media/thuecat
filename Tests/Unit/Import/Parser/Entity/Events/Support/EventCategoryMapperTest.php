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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Events\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventCategoryMapper;

class EventCategoryMapperTest extends TestCase
{
    private EventCategoryMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new EventCategoryMapper();
    }

    #[Test]
    public function mapsSingleKnownTypeToLabel(): void
    {
        $this->assertSame('Kulturveranstaltung', $this->mapper->titleFor('thuecat:CultureEvent'));
        $this->assertSame('Theaterveranstaltung', $this->mapper->titleFor('schema:TheaterEvent'));
    }

    #[Test]
    public function returnsNullForUnmappedType(): void
    {
        $this->assertNull($this->mapper->titleFor('schema:Event'));
        $this->assertNull($this->mapper->titleFor('schema:Thing'));
        $this->assertNull($this->mapper->titleFor('ttgds:Event'));
    }

    #[Test]
    public function prefixesASourceValueWithTheSourceField(): void
    {
        $this->assertSame('type:thuecat:CultureEvent', $this->mapper->prefixed('thuecat:CultureEvent'));
    }

    /**
     * `test:` is no ThueCat prefix, so the unmappable value stays unmappable.
     * A real URI here breaks these tests the day it gets a mapping.
     */
    #[Test]
    public function categoriesForKeepsOnlyMappedMembersOfATypeArray(): void
    {
        $types = [
            'schema:Thing',
            'schema:Event',
            'test:UnmappedByConstruction',
            'thuecat:CultureEvent',
            'dcmitype:Event',
            'ttgds:Event',
        ];

        $this->assertSame([['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung']], $this->mapper->categoriesFor($types));
    }

    #[Test]
    public function categoriesForKeepsFirstSeenOrderAcrossSeveralMappedMembers(): void
    {
        $types = [
            'schema:MusicEvent',
            'schema:Thing',
            'thuecat:CultureEvent',
        ];

        $this->assertSame([
            ['remoteId' => 'schema:MusicEvent', 'title' => 'Musik'],
            ['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung'],
        ], $this->mapper->categoriesFor($types));
    }

    #[Test]
    public function categoriesForDeduplicatesRepeatedType(): void
    {
        $types = [
            'thuecat:CultureEvent',
            'thuecat:CultureEvent',
        ];

        $this->assertSame([['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung']], $this->mapper->categoriesFor($types));
    }

    #[Test]
    public function categoriesForReturnsEmptyWhenNothingMaps(): void
    {
        $this->assertSame([], $this->mapper->categoriesFor(['schema:Thing', 'schema:Event']));
    }

    #[Test]
    public function reportSplitsTypesIntoMatchedAndUnmatched(): void
    {
        $types = [
            'schema:Thing',        // ignored (structural)
            'schema:Event',        // ignored (structural)
            'test:UnmappedByConstruction',  // unmatched
            'thuecat:CultureEvent', // matched
            'dcmitype:Event',      // ignored (structural)
            'ttgds:Event',         // ignored (structural)
        ];

        $this->assertSame([
            'matched' => ['thuecat:CultureEvent' => 'Kulturveranstaltung'],
            'unmatched' => ['test:UnmappedByConstruction'],
        ], $this->mapper->reportMatchStatus($types));
    }

    #[Test]
    public function reportDeduplicatesUnmatchedTypes(): void
    {
        $this->assertSame(['matched' => [], 'unmatched' => ['test:UnmappedByConstruction']], $this->mapper->reportMatchStatus(['test:UnmappedByConstruction', 'test:UnmappedByConstruction']));
    }

    #[Test]
    public function reportExcludesAnIgnoredTypeFromBothLists(): void
    {
        // schema:Thing is a representative ignored structural type. We assert it
        // lands in neither list rather than pinning the whole ignore set, so the
        // (intentionally sparse, growing) list can change without touching tests.
        $report = $this->mapper->reportMatchStatus(['schema:Thing', 'test:UnmappedByConstruction']);

        $this->assertArrayNotHasKey('schema:Thing', $report['matched']);
        $this->assertNotContains('schema:Thing', $report['unmatched']);
        // The non-ignored unmappable type is still reported.
        $this->assertContains('test:UnmappedByConstruction', $report['unmatched']);
    }
}
