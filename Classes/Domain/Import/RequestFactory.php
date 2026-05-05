<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Domain\Import;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class RequestFactory implements RequestFactoryInterface
{
    private ?string $apiKeyOverride = null;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly UriFactoryInterface $uriFactory
    ) {
    }

    /**
     * Returns a clone that uses the given per-ImportConfiguration key instead
     * of the global ExtensionConfiguration key. An empty string or null falls
     * back to the global key.
     */
    public function withApiKey(?string $apiKey): self
    {
        $clone = clone $this;
        $clone->apiKeyOverride = ($apiKey === null || $apiKey === '') ? null : $apiKey;
        return $clone;
    }

    /**
     * @param UriInterface|string $uri The URI associated with the request.
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (!$uri instanceof UriInterface) {
            $uri = $this->uriFactory->createUri((string)$uri);
        }

        $query = [];
        parse_str($uri->getQuery(), $query);
        $query = array_merge($query, [
            'format' => 'jsonld',
        ]);

        $apiKey = $this->resolveApiKey();
        if ($apiKey !== null) {
            $query['api_key'] = $apiKey;
        }

        $uri = $uri->withQuery(http_build_query($query));

        return $this->requestFactory->createRequest($method, $uri);
    }

    private function resolveApiKey(): ?string
    {
        if ($this->apiKeyOverride !== null) {
            return $this->apiKeyOverride;
        }

        try {
            $apiKey = $this->extensionConfiguration->get('thuecat', 'apiKey');
        } catch (ExtensionConfigurationExtensionNotConfiguredException) {
            return null;
        }

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }
}
