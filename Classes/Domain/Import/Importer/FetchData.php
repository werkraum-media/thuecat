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

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData\InvalidResponseException;

class FetchData
{
    private string $databaseUrlPrefix = 'https://cdb.thuecat.org';

    private string $urlPrefix = 'https://thuecat.org';

    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ClientInterface $httpClient,
        private readonly CacheFrontendInterface $cache
    ) {
    }

    public function updatedNodes(string $scopeId): array
    {
        return $this->jsonLDFromUrl(
            $this->databaseUrlPrefix
            . '/api/ext-sync/get-updated-nodes?syncScopeId='
            . urlencode($scopeId)
        );
    }

    public function getFullResourceUrl(string $id): string
    {
        return $this->getResourceEndpoint() . ltrim($id, '/');
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

        $this->handleInvalidResponse($response, $request);

        $jsonLD = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($jsonLD)) {
            $this->cache->set($cacheIdentifier, $jsonLD);
            return $jsonLD;
        }

        return [];
    }

    private function getResourceEndpoint(): string
    {
        return $this->urlPrefix . '/resources/';
    }

    private function handleInvalidResponse(
        ResponseInterface $response,
        RequestInterface $request
    ): void {
        if ($response->getStatusCode() === 200) {
            return;
        }

        if ($response->getStatusCode() === 401) {
            throw new InvalidResponseException(
                'Unauthorized API request, ensure apiKey is properly configured.',
                1622461709
            );
        }

        if ($response->getStatusCode() === 404) {
            throw new InvalidResponseException(
                sprintf(
                    'Not found, given resource could not be found: "%s".',
                    $request->getUri()
                ),
                1622461820
            );
        }
    }
}
