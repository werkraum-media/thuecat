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

namespace WerkraumMedia\ThueCat\Tests\Functional\Importer;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;

final class FetchDataTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsDecodedGraphFromSuccessfulResponse(): void
    {
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $result = $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');

        self::assertArrayHasKey('@graph', $result);
        self::assertIsArray($result['@graph']);
        self::assertNotEmpty($result['@graph']);
    }

    #[Test]
    public function secondCallForSameUrlIsServedFromCache(): void
    {
        // Only one response staged — a second HTTP hit would trip the
        // empty-bag error. Cache must absorb the second call.
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $first = $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');
        $second = $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');

        self::assertSame($first, $second);
    }

    #[Test]
    public function differentApiKeyBypassesCache(): void
    {
        // Same URL, different key → different cache slot → two HTTP hits
        // needed. The faker normalises away api_key before matching, so the
        // same URL declaration covers both calls.
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe', 'key-a');
        // Would throw if cache incorrectly merged both keys into one slot.
        $result = $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe', 'key-b');

        // Both responses consumed — two real HTTP calls, no cache collision.
        self::assertArrayHasKey('@graph', $result);
    }

    #[Test]
    public function apiKeyIsAppendedToRequestUri(): void
    {
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe', 'my-secret-key');

        $lastRequest = GuzzleClientFaker::getLastRequest();
        self::assertNotNull($lastRequest);
        parse_str($lastRequest->getUri()->getQuery(), $query);
        self::assertSame('my-secret-key', $query['api_key']);
    }

    #[Test]
    public function formatJsonldIsAlwaysAppendedToRequestUri(): void
    {
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');

        $lastRequest = GuzzleClientFaker::getLastRequest();
        self::assertNotNull($lastRequest);
        parse_str($lastRequest->getUri()->getQuery(), $query);
        self::assertSame('jsonld', $query['format']);
    }

    #[Test]
    public function formatJsonldOverridesExistingFormatParameterInUrl(): void
    {
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe?format=xml');

        $lastRequest = GuzzleClientFaker::getLastRequest();
        self::assertNotNull($lastRequest);
        parse_str($lastRequest->getUri()->getQuery(), $query);
        self::assertSame('jsonld', $query['format']);
    }

    #[Test]
    public function throwsOnNotFoundResponse(): void
    {
        $this->expectNotFound('018132452787-ngbe');

        $this->expectException(InvalidResponseException::class);

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');
    }

    #[Test]
    public function throwsOnUnauthorizedResponse(): void
    {
        GuzzleClientFaker::expectUnauthorizedForUrl('https://thuecat.org/resources/018132452787-ngbe');

        $this->expectException(InvalidResponseException::class);

        $subject = $this->get(FetchData::class);
        $subject->jsonLDFromUrl('https://thuecat.org/resources/018132452787-ngbe');
    }
}
