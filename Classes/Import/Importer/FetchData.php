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

namespace WerkraumMedia\ThueCat\Import\Importer;

use DateInterval;
use DateTimeZone;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;
use WerkraumMedia\ThueCat\Import\RequestFactory;

class FetchData
{
    /**
     * Single source of truth for the API host every import configuration
     * targets unless it explicitly overrides the value. Applied across all
     * configuration types (static, syncScope, containsPlace) — any caller
     * that needs to default outside this class (e.g. the ImportConfiguration
     * model when its flexform field is empty) must reuse this constant so
     * the fallback stays in one place.
     */
    public const DEFAULT_API_DOMAIN = 'https://cdb.thuecat.org';

    private string $urlPrefix = 'https://thuecat.org';

    public function __construct(
        #[Autowire(service: RequestFactory::class)]
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ClientInterface $httpClient,
        #[Autowire(service: 'cache.thuecat_fetchdata')]
        private readonly CacheFrontendInterface $cache
    ) {
    }

    public function updatedNodes(string $scopeId, ?string $apiKey = null, ?string $apiDomain = null, int $fetchLastXDays = 0): array
    {
        // Compute per-call so concurrent configurations using different hosts
        // don't trample each other. Empty/null falls back to the default —
        // never operate without an API domain.
        $domain = ($apiDomain === null || $apiDomain === '') ? self::DEFAULT_API_DOMAIN : $apiDomain;
        $domain = rtrim($domain, '/') . '/';
        $timezone = new DateTimeZone('Europe/Berlin');
        $from = '';
        if ($fetchLastXDays > 0) {
            $today = date_create_immutable('now', $timezone)->setTime(0, 0, 0, 0);
            $interval = new DateInterval('P' . $fetchLastXDays . 'D');
            $from = $today->sub($interval);
            $from = 'from=' . urlencode($from->format('c')) . '&';
        }
        return $this->jsonLDFromUrl(
            $domain
                . 'api/ext-sync/get-updated-nodes?showTotal=true&' . $from . 'syncScopeId='
                . urlencode($scopeId),
            $apiKey
        );
    }

    /**
     * Build the absolute resource URL for an id. The optional $apiDomain
     * lets URL providers route resource fetches at the same host they pulled
     * the sync-scope or contains-place response from — needed for
     * configurations whose entire conversation runs against `int.thuecat.org`
     * or another non-default host. Falls back to the canonical
     * `https://thuecat.org` resource URI host (which is what JSON-LD `@id`
     * URIs reference) when no per-config domain was threaded through.
     */
    public function getFullResourceUrl(string $id, ?string $apiDomain = null): string
    {
        $host = ($apiDomain === null || $apiDomain === '') ? $this->urlPrefix : $apiDomain;
        return rtrim($host, '/') . '/resources/' . ltrim($id, '/');
    }

    public function jsonLDFromUrl(string $url, ?string $apiKey = null): array
    {
        // Include the effective api key in the cache identifier so two
        // configurations with different keys don't share responses.
        $cacheIdentifier = sha1($url . '|' . ($apiKey ?? ''));
        $cacheEntry = $this->cache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }

        $requestFactory = ($apiKey !== null && $apiKey !== '' && $this->requestFactory instanceof RequestFactory)
            ? $this->requestFactory->withApiKey($apiKey)
            : $this->requestFactory;
        $request = $requestFactory->createRequest('GET', $url);
        $response = $this->httpClient->sendRequest($request);

        $this->handleInvalidResponse($response, $request);

        $jsonLD = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($jsonLD)) {
            $this->cache->set($cacheIdentifier, $jsonLD);
            return $jsonLD;
        }

        return [];
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
            throw new ResourceNotFoundException(
                sprintf(
                    'Not found, given resource could not be found: "%s".',
                    $request->getUri()
                ),
                1622461820
            );
        }
    }
}
