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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Places\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Places\Support\PlaceCategoryMapper;

final class PlaceCategoryMapperTest extends TestCase
{
    private PlaceCategoryMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new PlaceCategoryMapper();
    }

    #[Test]
    public function mapsSingleKnownTypeToLabel(): void
    {
        $this->assertSame('Museum', $this->mapper->titleFor('schema:Museum'));
        $this->assertSame('Kathedrale', $this->mapper->titleFor('thuecat:Cathedral'));
    }

    #[Test]
    public function returnsNullForUnmappedType(): void
    {
        $this->assertNull($this->mapper->titleFor('schema:Place'));
        $this->assertNull($this->mapper->titleFor('schema:TouristAttraction'));
        $this->assertNull($this->mapper->titleFor('test:UnmappedByConstruction'));
    }

    #[Test]
    public function prefixesASourceValueWithTheSourceField(): void
    {
        $this->assertSame('type:schema:Museum', $this->mapper->prefixed('schema:Museum'));
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
            'schema:Place',
            'schema:TouristAttraction',
            'ttgds:PointOfInterest',
            'test:UnmappedByConstruction',
            'schema:Museum',
        ];

        $this->assertSame([['remoteId' => 'schema:Museum', 'title' => 'Museum']], $this->mapper->categoriesFor($types));
    }

    #[Test]
    public function categoriesForKeepsFirstSeenOrderAcrossSeveralMappedMembers(): void
    {
        $types = [
            'schema:Synagogue',
            'schema:Place',
            'schema:Museum',
        ];

        $this->assertSame([
            ['remoteId' => 'schema:Synagogue', 'title' => 'Synagoge'],
            ['remoteId' => 'schema:Museum', 'title' => 'Museum'],
        ], $this->mapper->categoriesFor($types));
    }

    #[Test]
    public function reportSplitsTypesIntoMatchedAndUnmatched(): void
    {
        $types = [
            'schema:Thing',              // ignored (structural)
            'schema:Place',              // ignored (structural)
            'schema:TouristAttraction',  // ignored (structural)
            'ttgds:PointOfInterest',     // ignored (structural)
            'test:UnmappedByConstruction',          // unmatched
            'schema:Museum',             // matched
        ];

        $this->assertSame([
            'matched' => ['schema:Museum' => 'Museum'],
            'unmatched' => ['test:UnmappedByConstruction'],
        ], $this->mapper->reportMatchStatus($types));
    }

    #[Test]
    public function reportExcludesAnIgnoredTypeFromBothLists(): void
    {
        // Assert absence rather than pinning the whole (growing) ignore set.
        $report = $this->mapper->reportMatchStatus(['schema:Place', 'test:UnmappedByConstruction']);

        $this->assertArrayNotHasKey('schema:Place', $report['matched']);
        $this->assertNotContains('schema:Place', $report['unmatched']);
        $this->assertContains('test:UnmappedByConstruction', $report['unmatched']);
    }
}
