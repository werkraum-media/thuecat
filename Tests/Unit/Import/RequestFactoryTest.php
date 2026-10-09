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
}
