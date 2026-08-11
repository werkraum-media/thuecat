<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Events\Support;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Log\LogManager;
use WerkraumMedia\Events\Service\DestinationDataImportService\DatesFactory;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventDateFactory;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventLevelDateReader;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventScheduleAdapter;

// Routing is decided on the PRESENCE of schema:eventSchedule, never on whether
// it yields anything: in the mixed shape the event-level pair is the schedule's
// envelope, so falling back to it would build one long wrong occurrence.
class EventDateFactoryTest extends TestCase
{
    private EventDateFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->factoryWithToday('2026-01-01T00:00:00+01:00');
    }

    private function factoryWithToday(string $today): EventDateFactory
    {
        $context = new Context();
        $context->setAspect('date', new DateTimeAspect(new DateTimeImmutable($today)));

        return new EventDateFactory(
            new EventScheduleAdapter(),
            new EventLevelDateReader(),
            new DatesFactory($context, new LogManager()),
            $context
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function nodeStartingAt(string $start): array
    {
        return [
            'schema:startDate' => ['@type' => 'schema:DateTime', '@value' => $start],
            'schema:endDate' => ['@type' => 'schema:DateTime', '@value' => $start],
        ];
    }

    #[Test]
    public function readsEventLevelDatesWhenNoScheduleIsPresent(): void
    {
        $occurrences = $this->factory->toOccurrences($this->eventLevelNode());

        self::assertCount(1, $occurrences);
        self::assertSame('2026-12-01T08:00:00+01:00', $occurrences[0]->start);
        self::assertSame('2026-12-01T10:00:00+01:00', $occurrences[0]->end);
    }

    #[Test]
    public function readsScheduleWhenNoEventLevelDatesArePresent(): void
    {
        $occurrences = $this->factory->toOccurrences($this->scheduleNode());

        self::assertCount(1, $occurrences);
        self::assertSame('2026-12-05T14:00:00+01:00', $occurrences[0]->start);
    }

    // The mixed shape: 39 of 156 probed events. The event-level pair spans the
    // whole series, so reading it here would replace every occurrence with one.
    #[Test]
    public function prefersTheScheduleWhenBothArePresent(): void
    {
        $node = $this->scheduleNode();
        $node['schema:startDate'] = ['@type' => 'schema:Date', '@value' => '2026-12-05'];
        $node['schema:endDate'] = ['@type' => 'schema:Date', '@value' => '2026-12-26'];

        $occurrences = $this->factory->toOccurrences($node);

        self::assertCount(1, $occurrences);
        self::assertSame('2026-12-05T14:00:00+01:00', $occurrences[0]->start);
        self::assertSame('2026-12-05T16:00:00+01:00', $occurrences[0]->end);
    }

    // Presence, not yield: a schedule that expands to nothing still wins.
    #[Test]
    public function doesNotFallBackWhenThePresentScheduleYieldsNothing(): void
    {
        $node = $this->eventLevelNode();
        $node['schema:eventSchedule'] = [
            '@id' => 'genid-empty',
            '@type' => ['schema:Thing', 'schema:Schedule', 'schema:Intangible'],
        ];

        self::assertSame([], $this->factory->toOccurrences($node));
    }

    #[Test]
    public function returnsNothingWhenTheEventPublishesNoDatesAtAll(): void
    {
        self::assertSame([], $this->factory->toOccurrences([]));
    }

    #[Test]
    public function dropsAnEventOccurrenceThatLiesInThePast(): void
    {
        $factory = $this->factoryWithToday('2026-12-01T09:00:00+01:00');

        self::assertSame([], $factory->toOccurrences($this->nodeStartingAt('2026-11-30T20:00:00+01:00')));
    }

    #[Test]
    public function keepsAnEventOccurrenceLaterToday(): void
    {
        $factory = $this->factoryWithToday('2026-12-01T09:00:00+01:00');

        self::assertCount(1, $factory->toOccurrences($this->nodeStartingAt('2026-12-01T20:00:00+01:00')));
    }

    /**
     * The boundary where ext:events' two filters disagree: its SINGLE-date rule
     * is strict, its recurring rule is not. An event-level occurrence is a
     * single date, so the strict rule is the one to mirror.
     */
    #[Test]
    public function dropsAnEventOccurrenceStartingExactlyAtMidnightToday(): void
    {
        $factory = $this->factoryWithToday('2026-12-01T09:00:00+01:00');

        self::assertSame([], $factory->toOccurrences($this->nodeStartingAt('2026-12-01T00:00:00+01:00')));
    }

    #[Test]
    public function reportsAnUnresolvableEventLevelDate(): void
    {
        $node = $this->eventLevelNode();
        unset($node['schema:endDate']);

        self::assertSame([], $this->factory->toOccurrences($node));
        self::assertTrue($this->factory->hadUnresolvableEventLevelDate());
    }

    #[Test]
    public function reportsNothingUnresolvableForAUsableEventLevelDate(): void
    {
        $this->factory->toOccurrences($this->eventLevelNode());

        self::assertFalse($this->factory->hadUnresolvableEventLevelDate());
    }

    // A schedule-less event with no dates is not an unresolvable DATE — that
    // distinction is what keeps the two log entries answering different questions.
    #[Test]
    public function reportsNothingUnresolvableWhenNoDateKeysArePresent(): void
    {
        $this->factory->toOccurrences([]);

        self::assertFalse($this->factory->hadUnresolvableEventLevelDate());
    }

    #[Test]
    public function reportsNothingUnresolvableWhenAScheduleIsPresent(): void
    {
        $node = $this->scheduleNode();
        $node['schema:startDate'] = ['@type' => 'schema:Date', '@value' => '2026-12-05'];

        $this->factory->toOccurrences($node);

        self::assertFalse($this->factory->hadUnresolvableEventLevelDate());
    }

    /**
     * @return array<string, mixed>
     */
    private function eventLevelNode(): array
    {
        return [
            'schema:startDate' => ['@type' => 'schema:DateTime', '@value' => '2026-12-01T08:00:00.000+01:00'],
            'schema:endDate' => ['@type' => 'schema:DateTime', '@value' => '2026-12-01T10:00:00.000+01:00'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleNode(): array
    {
        return [
            'schema:eventSchedule' => [
                '@id' => 'genid-single',
                '@type' => ['schema:Thing', 'schema:Schedule', 'schema:Intangible'],
                'schema:startDate' => ['@type' => 'schema:DateTime', '@value' => '2026-12-05T14:00:00+01:00'],
                'schema:endTime' => ['@type' => 'schema:DateTime', '@value' => '2026-12-05T16:00:00+01:00'],
            ],
        ];
    }
}
