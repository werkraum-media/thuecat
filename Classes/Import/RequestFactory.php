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

namespace WerkraumMedia\ThueCat\Import;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class RequestFactory implements RequestFactoryInterface
{
    protected ?string $apiKeyOverride = null;

    protected ?string $syncHost = null;

    protected ?string $syncApiKey = null;

    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly UriFactoryInterface $uriFactory
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
     * Returns a clone that authenticates requests to the given host the way the
     * sync API expects. The key is confined to that host. Every other host of a
     * sync run gets no key: the resource and asset hosts are unprotected.
     */
    public function withSyncHost(string $host, string $apiKey): self
    {
        $clone = clone $this;
        $clone->syncHost = $host;
        $clone->syncApiKey = $apiKey;
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

        if ($this->syncHost !== null && strcasecmp($uri->getHost(), $this->syncHost) === 0) {
            return $this->requestFactory
                ->createRequest($method, $uri)
                ->withHeader('Authorization', 'APIKEY ' . $this->syncApiKey)
            ;
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

    protected function resolveApiKey(): ?string
    {
        if ($this->syncHost !== null) {
            return null;
        }

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
