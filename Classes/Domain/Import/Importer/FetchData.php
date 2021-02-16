<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;

class FetchData
{
    private RequestFactoryInterface $requestFactory;
    private ClientInterface $httpClient;
    private CacheFrontendInterface $cache;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        ClientInterface $httpClient,
        CacheFrontendInterface $cache
    ) {
        $this->requestFactory = $requestFactory;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    public function jsonLDFromUrl(string $url): array
    {
        $cacheIdentifier = sha1($url);
        $cacheEntry = $this->cache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->httpClient->sendRequest($request);

        $jsonLD = json_decode((string) $response->getBody(), true);
        if (is_array($jsonLD)) {
            $this->cache->set($cacheIdentifier, $jsonLD);
            return $jsonLD;
        }

        return [];
    }
}
