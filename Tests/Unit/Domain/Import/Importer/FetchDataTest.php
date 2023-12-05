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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData\InvalidResponseException;

class FetchDataTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        self::assertInstanceOf(FetchData::class, $subject);
    }

    #[Test]
    public function returnsParsedJsonLdBasedOnUrl(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = $this->createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('{"@graph":[{"@id":"https://example.com/resources/018132452787-ngbe"}]}');

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
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

    #[Test]
    public function returnsEmptyArrayInCaseOfError(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient->method('sendRequest')->willReturn($response);

        $body = $this->createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('[]');

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $result = $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
        self::assertSame([], $result);
    }

    #[Test]
    public function returnsResultFromCacheIfAvailable(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $cache->method('get')->willReturn([
            '@graph' => [
                [
                    '@id' => 'https://example.com/resources/018132452787-ngbe',
                ],
            ],
        ]);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
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

    #[Test]
    public function throwsExceptionOn404(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $uri = $this->createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('https://example.com/resources/018132452787-ngbe');
        $request->method('getUri')->willReturn($uri);

        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient->method('sendRequest')->willReturn($response);

        $body = $this->createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('{"error":"404"}');

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionCode(1622461820);
        $this->expectExceptionMessage('Not found, given resource could not be found: "https://example.com/resources/018132452787-ngbe".');

        $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
    }

    #[Test]
    public function throwsExceptionOn401(): void
    {
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $cache = $this->createStub(FrontendInterface::class);

        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient->method('sendRequest')->willReturn($response);

        $response->method('getStatusCode')->willReturn(401);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionCode(1622461709);
        $this->expectExceptionMessage('Unauthorized API request, ensure apiKey is properly configured.');

        $subject->jsonLDFromUrl('https://example.com/resources/018132452787-ngbe');
    }
}
