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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;

final class ParserTest extends AbstractImportTestCase
{
    private const FIXTURE_PATH = __DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/';

    #[Test]
    public function parsesOrganisationNode(): void
    {
        $graph = $this->graphFromFixture('018132452787-ngbe.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        self::assertArrayHasKey('tx_thuecat_organisation', $result);
        self::assertArrayHasKey(
            'https://thuecat.org/resources/018132452787-ngbe',
            $result['tx_thuecat_organisation']
        );

        $row = $result['tx_thuecat_organisation']['https://thuecat.org/resources/018132452787-ngbe'];

        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
    }

    #[Test]
    public function parsesTouristInformationNode(): void
    {
        $graph = $this->graphFromFixture('333039283321-xxwg.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        self::assertArrayHasKey('tx_thuecat_tourist_information', $result);
        self::assertArrayHasKey(
            'https://thuecat.org/resources/333039283321-xxwg',
            $result['tx_thuecat_tourist_information']
        );

        $row = $result['tx_thuecat_tourist_information']['https://thuecat.org/resources/333039283321-xxwg'];

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $row['remote_id']);
        self::assertSame('Erfurt Tourist Information', $row['title']);
    }

    #[Test]
    public function skipsBlankNodes(): void
    {
        $graph = $this->graphFromFixture('018132452787-ngbe.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        foreach (array_keys($result) as $table) {
            foreach (array_keys($result[$table]) as $remoteId) {
                self::assertStringNotContainsString('genid-', $remoteId);
            }
        }
    }

    private function graphFromFixture(string $filename): array
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        return is_array($graph) ? $graph : [];
    }
}
