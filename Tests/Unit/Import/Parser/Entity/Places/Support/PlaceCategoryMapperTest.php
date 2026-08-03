<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Places\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Places\Support\PlaceCategoryMapper;

class PlaceCategoryMapperTest extends TestCase
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
        self::assertSame('Museum', $this->mapper->titleFor('schema:Museum'));
        self::assertSame('Kathedrale', $this->mapper->titleFor('thuecat:Cathedral'));
    }

    #[Test]
    public function returnsNullForUnmappedType(): void
    {
        self::assertNull($this->mapper->titleFor('schema:Place'));
        self::assertNull($this->mapper->titleFor('schema:TouristAttraction'));
        self::assertNull($this->mapper->titleFor('test:UnmappedByConstruction'));
    }

    #[Test]
    public function prefixesASourceValueWithTheSourceField(): void
    {
        self::assertSame('type:schema:Museum', $this->mapper->prefixed('schema:Museum'));
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

        self::assertSame(
            [['remoteId' => 'schema:Museum', 'title' => 'Museum']],
            $this->mapper->categoriesFor($types)
        );
    }

    #[Test]
    public function categoriesForKeepsFirstSeenOrderAcrossSeveralMappedMembers(): void
    {
        $types = [
            'schema:Synagogue',
            'schema:Place',
            'schema:Museum',
        ];

        self::assertSame(
            [
                ['remoteId' => 'schema:Synagogue', 'title' => 'Synagoge'],
                ['remoteId' => 'schema:Museum', 'title' => 'Museum'],
            ],
            $this->mapper->categoriesFor($types)
        );
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

        self::assertSame(
            [
                'matched' => ['schema:Museum' => 'Museum'],
                'unmatched' => ['test:UnmappedByConstruction'],
            ],
            $this->mapper->reportMatchStatus($types)
        );
    }

    #[Test]
    public function reportExcludesAnIgnoredTypeFromBothLists(): void
    {
        // Assert absence rather than pinning the whole (growing) ignore set.
        $report = $this->mapper->reportMatchStatus(['schema:Place', 'test:UnmappedByConstruction']);

        self::assertArrayNotHasKey('schema:Place', $report['matched']);
        self::assertNotContains('schema:Place', $report['unmatched']);
        self::assertContains('test:UnmappedByConstruction', $report['unmatched']);
    }
}
