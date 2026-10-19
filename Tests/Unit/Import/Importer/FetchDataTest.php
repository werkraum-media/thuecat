<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Importer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;

class FetchDataTest extends TestCase
{
    #[Test]
    public function returnsParsedJsonLdBasedOnUrl(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
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
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
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
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

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
    public function ignoringCacheNeitherReadsNorWritesTheCache(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = $this->createMock(FrontendInterface::class);
        $cache->expects(self::never())->method('get');
        $cache->expects(self::never())->method('set');

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('{"expired":["e_1-test"]}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        self::assertSame(
            ['expired' => ['e_1-test']],
            $subject->jsonLDFromUrlIgnoringCache('https://example.com/api/sync/test-channel/list-expired')
        );
    }

    #[Test]
    public function throwsExceptionOn404(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $uri = self::createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('https://example.com/resources/018132452787-ngbe');
        $request->method('getUri')->willReturn($uri);

        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
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
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

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

    #[Test]
    public function throwsNotFoundOnEmptyBody(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $uri = self::createStub(UriInterface::class);
        $uri->method('__toString')->willReturn('https://widgets.thuecat.org/api/sync/test-channel/jsonld/e_test-event');
        $request->method('getUri')->willReturn($uri);

        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('');

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('"https://widgets.thuecat.org/api/sync/test-channel/jsonld/e_test-event"');

        $subject->jsonLDFromUrl('https://widgets.thuecat.org/api/sync/test-channel/jsonld/e_test-event');
    }

    #[Test]
    public function wrapsBareNodeIntoGraph(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('{"@id":"https://thuecat.org/resources/e_test-event","@type":["schema:Event"]}');

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $result = $subject->jsonLDFromUrl('https://widgets.thuecat.org/api/sync/test-channel/jsonld/e_test-event');
        self::assertSame([
            '@graph' => [
                [
                    '@id' => 'https://thuecat.org/resources/e_test-event',
                    '@type' => ['schema:Event'],
                ],
            ],
        ], $result);
    }

    #[Test]
    public function keepsResponseWithoutNodeUnwrapped(): void
    {
        $requestFactory = self::createStub(RequestFactoryInterface::class);
        $httpClient = self::createStub(ImportHttpClient::class);
        $cache = self::createStub(FrontendInterface::class);

        $request = self::createStub(RequestInterface::class);
        $response = self::createStub(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $httpClient->method('sendRequest')->willReturn($response);

        $body = self::createStub(StreamInterface::class);
        $body->method('__toString')->willReturn('{"data":{"createdOrUpdated":["835224016581-dara"],"removed":[]}}');

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        $subject = new FetchData(
            $requestFactory,
            $httpClient,
            $cache
        );

        $result = $subject->jsonLDFromUrl('https://cdb.thuecat.org/api/ext-sync/get-updated-nodes?syncScopeId=dd4615dc-58a6-4648-a7ce-4950293a06db');
        self::assertSame([
            'data' => [
                'createdOrUpdated' => ['835224016581-dara'],
                'removed' => [],
            ],
        ], $result);
    }
}
