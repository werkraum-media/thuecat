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

namespace WerkraumMedia\ThueCat\Tests\Functional\Resolver;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Domain\Import\Resolver;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class ResolverTest extends AbstractImportTestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../Fixtures/Import/Guzzle/thuecat.org/resources/';

    private const ORG_REMOTE_ID = 'https://thuecat.org/resources/018132452787-ngbe';

    #[Test]
    public function freshOrganisationGetsNewPlaceholderKey(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BasicPages.php');

        $payload = $this->parseFixture('018132452787-ngbe.json');

        $this->get(Resolver::class)->resolve($payload, 10);

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_organisation'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_organisation']);
        self::assertCount(1, $keys);
        self::assertStringStartsWith('NEW', (string)$keys[0]);

        $row = $data['tx_thuecat_organisation'][$keys[0]];
        self::assertSame(self::ORG_REMOTE_ID, $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertSame(10, $row['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function existingOrganisationIsKeyedByUidAndDataOverwritten(): void
    {
        // Preloaded row has uid=1, remote_id matching the fixture, title 'Old title'.
        // We expect the resolver to key the row by '1' and leave the fresh data
        // (new title from the fixture) intact — no diffing, legacy behaviour.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/UpdatesExistingOrganization.php');

        $payload = $this->parseFixture('018132452787-ngbe.json');

        $this->get(Resolver::class)->resolve($payload, 10);

        $data = $payload->getPayload();
        // PHP casts numeric-string array keys back to int automatically, so the
        // outer key the resolver sets as '1' ends up as int 1 in the array.
        self::assertSame([1], array_keys($data['tx_thuecat_organisation']));

        $row = $data['tx_thuecat_organisation'][1];
        self::assertSame(self::ORG_REMOTE_ID, $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertSame(10, $row['pid']);
    }

    private function parseFixture(string $filename): \WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) && is_array($decoded['@graph'] ?? null) ? $decoded['@graph'] : [];

        $parser = $this->get(Parser::class);
        $parser->parse($graph);
        return $parser->getDataHandlerPayload();
    }
}
