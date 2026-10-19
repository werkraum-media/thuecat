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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory as Typo3RequestFactory;
use TYPO3\CMS\Core\Http\UriFactory;
use WerkraumMedia\ThueCat\Import\RequestFactory;

class RequestFactoryTest extends TestCase
{
    #[Test]
    public function returnsRequestWithJsonIdFormat(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $requestFactory = new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class));
        $uriFactory = new UriFactory();

        $subject = new RequestFactory(
            $extensionConfiguration,
            $requestFactory,
            $uriFactory
        );

        $request = $subject->createRequest('GET', 'https://example.com/api/ext-sync/get-updated-nodes?syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db');

        self::assertSame('syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db&format=jsonld', $request->getUri()->getQuery());
    }

    #[Test]
    public function returnsRequestWithApiKeyWhenConfigured(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn('some-api-key');
        $requestFactory = new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class));
        $uriFactory = new UriFactory();

        $subject = new RequestFactory(
            $extensionConfiguration,
            $requestFactory,
            $uriFactory
        );

        $request = $subject->createRequest('GET', 'https://example.com/api/ext-sync/get-updated-nodes?syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db');

        self::assertSame('syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db&format=jsonld&api_key=some-api-key', $request->getUri()->getQuery());
    }

    #[Test]
    public function returnsRequestWithoutApiKeyWhenUnkown(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(new ExtensionConfigurationExtensionNotConfiguredException());
        $requestFactory = new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class));
        $uriFactory = new UriFactory();

        $subject = new RequestFactory(
            $extensionConfiguration,
            $requestFactory,
            $uriFactory
        );

        $request = $subject->createRequest('GET', 'https://example.com/api/ext-sync/get-updated-nodes?syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db');

        self::assertSame('syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db&format=jsonld', $request->getUri()->getQuery());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function thuecatHosts(): iterable
    {
        yield 'resource host' => ['https://thuecat.org/resources/test-resource'];
        yield 'asset host' => ['https://cdb.thuecat.org/resources/test-resource'];
    }

    #[Test]
    #[DataProvider('thuecatHosts')]
    public function keepsQueryCredentialsOnThuecatHosts(string $url): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn('some-api-key');

        $subject = new RequestFactory(
            $extensionConfiguration,
            new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class)),
            new UriFactory()
        );

        $request = $subject->createRequest('GET', $url);

        self::assertSame('format=jsonld&api_key=some-api-key', $request->getUri()->getQuery());
        self::assertFalse($request->hasHeader('Authorization'));
    }

    #[Test]
    public function sendsSyncKeyAsHeaderToSyncHost(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn('some-api-key');

        $subject = (new RequestFactory(
            $extensionConfiguration,
            new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class)),
            new UriFactory()
        ))->withSyncHost('widgets.thuecat.org', 'test-sync-key');

        $request = $subject->createRequest('GET', 'https://widgets.thuecat.org/api/sync/test-channel/list-changed?since=2026-10-14T03%3A00%3A00%2B02%3A00');

        self::assertSame('APIKEY test-sync-key', $request->getHeaderLine('Authorization'));
        self::assertSame('since=2026-10-14T03%3A00%3A00%2B02%3A00', $request->getUri()->getQuery());
    }

    #[Test]
    #[DataProvider('thuecatHosts')]
    public function sendsNoKeyToOtherHostsOfSyncRun(string $url): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn('some-api-key');

        $subject = (new RequestFactory(
            $extensionConfiguration,
            new Typo3RequestFactory(self::createStub(GuzzleClientFactory::class)),
            new UriFactory()
        ))->withSyncHost('widgets.thuecat.org', 'test-sync-key');

        $request = $subject->createRequest('GET', $url);

        self::assertFalse($request->hasHeader('Authorization'));
        self::assertSame('format=jsonld', $request->getUri()->getQuery());
    }
}
