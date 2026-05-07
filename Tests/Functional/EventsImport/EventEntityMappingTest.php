<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// Direct mapping smoke test: decode a Guzzle-fixture JSON-LD payload and feed
// its single @graph node to EventEntity::parse(). Asserts the entity's flat
// event row matches the expected shape and that getDates() returns the
// expanded per-occurrence rows. v1 covers event row + dates only — nested
// location/organizer rows land in a follow-up.
class EventEntityMappingTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    #[Test]
    public function mapsKreuzchorFixtureToExpectedRows(): void
    {
        $this->assertFixtureMapsTo('e_19542-hubev', 'KreuzchorMapping');
    }

    #[Test]
    public function mapsDistelFixtureWithRecurringScheduleToExpectedRows(): void
    {
        $this->assertFixtureMapsTo('e_100771372-hubev', 'DistelMapping');
    }

    private function assertFixtureMapsTo(string $fixtureId, string $assertionFile): void
    {
        $fixture = __DIR__ . '/Fixtures/Guzzle/cdb.int.thuecat.org/api/resources/' . $fixtureId . '.json';
        $expected = require __DIR__ . '/Assertions/' . $assertionFile . '.php';

        $payload = json_decode((string)file_get_contents($fixture), true);
        self::assertIsArray($payload);
        $graph = $payload['@graph'] ?? [];
        self::assertIsArray($graph);
        self::assertArrayHasKey(0, $graph);
        self::assertIsArray($graph[0]);

        $entity = new EventEntity();
        $entity->parse($graph[0], 'de', new ParserContext(0), []);

        self::assertIsArray($expected);
        self::assertSame($expected['event'], $entity->toArray());
        self::assertSame($expected['dates'], $entity->getDates());
    }
}
