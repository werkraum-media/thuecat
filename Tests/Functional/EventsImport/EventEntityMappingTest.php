<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Direct mapping smoke test: Asserts the entity's flat
 * event row matches the expected shape and that getDates() returns the
 * expanded per-occurrence rows.
 * This is the pre-Resolver view, so the `location` column still holds the
 * venue's content hash rather than its uid. The end-to-end result is asserted
 * by EventLocationImportTest.
 */
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
        $dateTime = new DateTimeImmutable('2024-09-19T00:00:00+00:00');
        $this->getContainer()->get(Context::class)->setAspect('date', new DateTimeAspect($dateTime));
        $this->assertFixtureMapsTo('e_100771372-hubev', 'DistelMapping');
    }

    #[Test]
    public function mapsWeeklyScheduleWithNonWeekdayByDayToExpectedRows(): void
    {
        $dateTime = new DateTimeImmutable('2026-12-01T00:00:00+00:00');
        $this->getContainer()->get(Context::class)->setAspect('date', new DateTimeAspect($dateTime));
        $this->assertFixtureMapsTo('e_7cbe5bb1-tdm', 'NonWeekdayByDayMapping');
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

        /** @var array<string, mixed> $node JSON objects decode to string-keyed arrays. */
        $node = $graph[0];
        $entity = new EventEntity();
        $entity->parse($node, 'de', new ParserContext(0), []);

        self::assertIsArray($expected);
        self::assertSame($expected['event'], $entity->toArray());
        self::assertSame($expected['dates'], $entity->getDates());
        self::assertSame($expected['categories'], $entity->getCategories());
    }
}
