<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import;

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
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory as Typo3RequestFactory;
use TYPO3\CMS\Core\Http\UriFactory;
use WerkraumMedia\ThueCat\Domain\Import\RequestFactory;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\RequestFactory
 */
class RequestFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $requestFactory = $this->createStub(Typo3RequestFactory::class);
        $uriFactory = new UriFactory();

        $subject = new RequestFactory(
            $extensionConfiguration,
            $requestFactory,
            $uriFactory
        );

        self::assertInstanceOf(RequestFactory::class, $subject);
    }

    /**
     * @test
     */
    public function returnsRequestWithJsonIdFormat(): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $requestFactory = new Typo3RequestFactory($this->createStub(GuzzleClientFactory::class));
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
     * @test
     */
    public function returnsRequestWithApiKeyWhenConfigured(): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn('some-api-key');
        $requestFactory = new Typo3RequestFactory($this->createStub(GuzzleClientFactory::class));
        $uriFactory = new UriFactory();

        $subject = new RequestFactory(
            $extensionConfiguration,
            $requestFactory,
            $uriFactory
        );

        $request = $subject->createRequest('GET', 'https://example.com/api/ext-sync/get-updated-nodes?syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db');

        self::assertSame('syncScopeId=dd3738dc-58a6-4748-a6ce-4950293a06db&format=jsonld&api_key=some-api-key', $request->getUri()->getQuery());
    }

    /**
     * @test
     */
    public function returnsRequestWithoutApiKeyWhenUnkown(): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(new ExtensionConfigurationExtensionNotConfiguredException());
        $requestFactory = new Typo3RequestFactory($this->createStub(GuzzleClientFactory::class));
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
