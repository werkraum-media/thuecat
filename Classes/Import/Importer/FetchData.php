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
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;
use WerkraumMedia\ThueCat\Import\Http\RetryExhaustedException;
use WerkraumMedia\ThueCat\Import\Http\RetryingClient;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;
use WerkraumMedia\ThueCat\Import\RequestFactory;

class FetchData
{
    // Reuse rather than re-defaulting elsewhere; the fallback lives here.
    public const DEFAULT_API_DOMAIN = 'https://cdb.thuecat.org';

    private string $urlPrefix = 'https://thuecat.org';

    private bool $bypassCache = false;

    private int $cacheLifetime = 0;

    public function __construct(
        #[Autowire(service: RequestFactory::class)]
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ImportHttpClient $httpClient,
        #[Autowire(service: 'cache.thuecat_fetchdata')]
        private readonly CacheFrontendInterface $cache
    ) {
    }

    /**
     * Per-run, on a shared service: the Importer sets this at run start and
     * clears it at the end, so one run's choices never bleed into the next.
     *
     * @param int $lifetime Seconds; 0 keeps the backend's default.
     */
    public function configureForRun(bool $bypassCache, int $lifetime = 0): void
    {
        $this->bypassCache = $bypassCache;
        $this->cacheLifetime = $lifetime;
    }

    public function resetRunConfiguration(): void
    {
        $this->bypassCache = false;
        $this->cacheLifetime = 0;
    }

    public function updatedNodes(string $scopeId, ?string $apiKey = null, ?string $apiDomain = null, int $fetchLastXDays = 0): array
    {
        // Per-call: concurrent configurations may target different hosts.
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

    // $apiDomain keeps resource fetches on the host the run is already talking
    // to; without it the canonical host that @id URIs reference applies.
    public function getFullResourceUrl(string $id, ?string $apiDomain = null): string
    {
        $host = ($apiDomain === null || $apiDomain === '') ? $this->urlPrefix : $apiDomain;
        return rtrim($host, '/') . '/resources/' . ltrim($id, '/');
    }

    public function jsonLDFromUrl(string $url, ?string $apiKey = null): array
    {
        // Keyed on the api key too: different keys must not share responses.
        $cacheIdentifier = sha1($url . '|' . ($apiKey ?? ''));
        // A bypassing run still writes, so the fresh response is what a later
        // run reads; only the read is skipped.
        if ($this->bypassCache === false) {
            $cacheEntry = $this->cache->get($cacheIdentifier);
            if (is_array($cacheEntry)) {
                return $cacheEntry;
            }
        }

        $requestFactory = ($apiKey !== null && $apiKey !== '' && $this->requestFactory instanceof RequestFactory)
            ? $this->requestFactory->withApiKey($apiKey)
            : $this->requestFactory;
        $request = $requestFactory->createRequest('GET', $url);
        $response = $this->httpClient->sendRequest($request);

        $this->handleInvalidResponse($response, $request);

        $jsonLD = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($jsonLD)) {
            $this->cache->set(
                $cacheIdentifier,
                $jsonLD,
                [],
                $this->cacheLifetime > 0 ? $this->cacheLifetime : null
            );
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

        // The import client runs with http_errors off so the media path can
        // inspect a status; that also means nothing else raises here. Without
        // this, a 5xx body reaches json_decode as an unrelated JsonException.
        throw new InvalidResponseException(
            sprintf(
                'Request to "%s" failed with status %d.',
                $request->getUri(),
                $response->getStatusCode()
            ),
            1786512025,
            $this->exhaustedRetry($response, $request)
        );
    }

    /**
     * A 5xx that survived every attempt comes back as a response, so the count
     * rides on a header; chain it so the log can report cause and attempts.
     */
    private function exhaustedRetry(
        ResponseInterface $response,
        RequestInterface $request
    ): ?RetryExhaustedException {
        $attempts = (int)$response->getHeaderLine(RetryingClient::ATTEMPTS_HEADER);
        if ($attempts < 2) {
            return null;
        }

        return new RetryExhaustedException(
            sprintf('Giving up on %s after %d attempts.', (string)$request->getUri(), $attempts),
            1786512026,
            $attempts,
            (string)$request->getUri()
        );
    }
}
