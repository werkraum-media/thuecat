<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristInformationEntity;
use WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Fake\ParserContextFake;

final class TouristInformationEntityTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../Fixtures/';

    #[Test]
    public function returnsCorrectTable(): void
    {
        $subject = new TouristInformationEntity();

        self::assertSame('tx_thuecat_tourist_information', $subject->table);
    }

    #[Test]
    public function handlesTouristInformationType(): void
    {
        $subject = new TouristInformationEntity();

        self::assertSame(['thuecat:TouristInformation'], $subject->handlesTypes());
    }

    #[Test]
    public function rowContainsRemoteId(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->configure($node, new ParserContextFake());

        $row = $subject->toArray();

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $row['remote_id']);
    }

    private function nodeFromFixture(string $filename): ?array
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        foreach ($graph as $node) {
            // The fixture is the TouristInformation node; locate it by its own @type.
            if (is_array($node) && in_array('thuecat:TouristInformation', $node['@type'] ?? [], true)) {
                return $node;
            }
        }
        return null;
    }
}
