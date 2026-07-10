<?php

declare(strict_types=1);

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
        self::assertSame('Kulturveranstaltung', $this->mapper->titleFor('thuecat:CultureEvent'));
        self::assertSame('Theaterveranstaltung', $this->mapper->titleFor('schema:TheaterEvent'));
    }

    #[Test]
    public function returnsNullForUnmappedType(): void
    {
        self::assertNull($this->mapper->titleFor('schema:Event'));
        self::assertNull($this->mapper->titleFor('schema:Thing'));
        self::assertNull($this->mapper->titleFor('ttgds:Event'));
    }

    #[Test]
    public function prefixesASourceValueWithTheSourceField(): void
    {
        self::assertSame('type:thuecat:CultureEvent', $this->mapper->prefixed('thuecat:CultureEvent'));
    }

    #[Test]
    public function categoriesForKeepsOnlyMappedMembersOfATypeArray(): void
    {
        $types = [
            'schema:Thing',
            'schema:Event',
            'schema:ComedyEvent',
            'thuecat:CultureEvent',
            'dcmitype:Event',
            'ttgds:Event',
        ];

        self::assertSame(
            [['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung']],
            $this->mapper->categoriesFor($types)
        );
    }

    #[Test]
    public function categoriesForKeepsFirstSeenOrderAcrossSeveralMappedMembers(): void
    {
        $types = [
            'schema:MusicEvent',
            'schema:Thing',
            'thuecat:CultureEvent',
        ];

        self::assertSame(
            [
                ['remoteId' => 'schema:MusicEvent', 'title' => 'Musik'],
                ['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung'],
            ],
            $this->mapper->categoriesFor($types)
        );
    }

    #[Test]
    public function categoriesForDeduplicatesRepeatedType(): void
    {
        $types = [
            'thuecat:CultureEvent',
            'thuecat:CultureEvent',
        ];

        self::assertSame(
            [['remoteId' => 'thuecat:CultureEvent', 'title' => 'Kulturveranstaltung']],
            $this->mapper->categoriesFor($types)
        );
    }

    #[Test]
    public function categoriesForReturnsEmptyWhenNothingMaps(): void
    {
        self::assertSame([], $this->mapper->categoriesFor(['schema:Thing', 'schema:Event']));
    }

    #[Test]
    public function reportSplitsTypesIntoMatchedAndUnmatched(): void
    {
        $types = [
            'schema:Thing',        // ignored (structural)
            'schema:Event',        // ignored (structural)
            'schema:ComedyEvent',  // unmatched
            'thuecat:CultureEvent', // matched
            'dcmitype:Event',      // ignored (structural)
            'ttgds:Event',         // ignored (structural)
        ];

        self::assertSame(
            [
                'matched' => ['thuecat:CultureEvent' => 'Kulturveranstaltung'],
                'unmatched' => ['schema:ComedyEvent'],
            ],
            $this->mapper->reportMatchStatus($types)
        );
    }

    #[Test]
    public function reportDeduplicatesUnmatchedTypes(): void
    {
        self::assertSame(
            ['matched' => [], 'unmatched' => ['schema:ComedyEvent']],
            $this->mapper->reportMatchStatus(['schema:ComedyEvent', 'schema:ComedyEvent'])
        );
    }

    #[Test]
    public function reportExcludesAnIgnoredTypeFromBothLists(): void
    {
        // schema:Thing is a representative ignored structural type. We assert it
        // lands in neither list rather than pinning the whole ignore set, so the
        // (intentionally sparse, growing) list can change without touching tests.
        $report = $this->mapper->reportMatchStatus(['schema:Thing', 'schema:ComedyEvent']);

        self::assertArrayNotHasKey('schema:Thing', $report['matched']);
        self::assertNotContains('schema:Thing', $report['unmatched']);
        // The non-ignored unmappable type is still reported.
        self::assertContains('schema:ComedyEvent', $report['unmatched']);
    }
}
