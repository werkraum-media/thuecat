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

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

/**
 * Direct mapping smoke test: Asserts the entity's flat
 * event row matches the expected shape and that getDates() returns the
 * expanded per-occurrence rows.
 * This is the pre-Resolver view, so the `location` column still holds the
 * venue's content hash rather than its uid. The end-to-end result is asserted
 * by EventLocationImportTest.
 */
final class EventEntityMappingTest extends AbstractFunctionalTestCase
{
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
        $this->assertIsArray($payload);
        $graph = $payload['@graph'] ?? [];
        $this->assertIsArray($graph);
        $this->assertArrayHasKey(0, $graph);
        $this->assertIsArray($graph[0]);

        /** @var array<string, mixed> $node JSON objects decode to string-keyed arrays. */
        $node = $graph[0];
        $entity = new EventEntity();
        $entity->parse($node, 'de', new ParserContext(0), []);

        $this->assertIsArray($expected);
        $this->assertSame($expected['event'], $entity->toArray());
        $this->assertSame($expected['dates'], $entity->getDates());
        $this->assertSame($expected['categories'], $entity->getCategories());
    }
}
