<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Importer;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData
 */
class FetchDataTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $httpClient = $this->prophesize(ClientInterface::class);
        $cache = $this->prophesize(FrontendInterface::class);

        $subject = new FetchData(
            $requestFactory->reveal(),
            $httpClient->reveal(),
            $cache->reveal()
        );

        self::assertInstanceOf(FetchData::class, $subject);
    }

    /**
     * @test
     */
    public function returnsParsedJsonLdBasedOnUrl(): void
    {
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $httpClient = $this->prophesize(ClientInterface::class);
        $cache = $this->prophesize(FrontendInterface::class);

        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);

        $requestFactory->createRequest('GET', 'https://example.com/resources/018132452787-ngbe')
            ->willReturn($request->reveal());

        $httpClient->sendRequest($request->reveal())
            ->willReturn($response->reveal());

        $response->getBody()->willReturn('{"@graph":[{"@id":"https://example.com/resources/018132452787-ngbe"}]}');

        $subject = new FetchData(
            $requestFactory->reveal(),
            $httpClient->reveal(),
            $cache->reveal()
        );

        $result = $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
        self::assertSame([
            '@graph' => [
                [
                    '@id' => 'https://example.com/resources/018132452787-ngbe',
                ],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayInCaseOfError(): void
    {
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $httpClient = $this->prophesize(ClientInterface::class);
        $cache = $this->prophesize(FrontendInterface::class);

        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);

        $requestFactory->createRequest('GET', 'https://example.com/resources/018132452787-ngbe')
            ->willReturn($request->reveal());

        $httpClient->sendRequest($request->reveal())
            ->willReturn($response->reveal());

        $response->getBody()->willReturn('');

        $subject = new FetchData(
            $requestFactory->reveal(),
            $httpClient->reveal(),
            $cache->reveal()
        );

        $result = $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsResultFromCacheIfAvailable(): void
    {
        $requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $httpClient = $this->prophesize(ClientInterface::class);
        $cache = $this->prophesize(FrontendInterface::class);

        $cache->get('03c8a7eb2a06e47c28883d95f7e834089baf9c3e')->willReturn([
            '@graph' => [
                [
                    '@id' => 'https://example.com/resources/018132452787-ngbe',
                ],
            ],
        ]);

        $subject = new FetchData(
            $requestFactory->reveal(),
            $httpClient->reveal(),
            $cache->reveal()
        );

        $result = $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
        self::assertSame([
            '@graph' => [
                [
                    '@id' => 'https://example.com/resources/018132452787-ngbe',
                ],
            ],
        ], $result);
    }
}
