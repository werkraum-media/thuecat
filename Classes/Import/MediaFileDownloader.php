<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Import;

use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;
use WerkraumMedia\ThueCat\Import\Http\RetryExhaustedException;
use WerkraumMedia\ThueCat\Import\Http\RetryingClient;

/**
 * Downloads one imported record's media file into the FAL staging folder.
 *
 * Names files after the download URL so re-imports and shared assets reuse
 * instead of re-fetch.
 * Sends via the PSR-18 client, not RequestFactory::request(): a 4xx/5xx must
 * come back as a response so one unfetchable image can be skipped.
 */
#[Autoconfigure(public: true)]
class MediaFileDownloader
{
    private const MAX_REDIRECTS = 5;

    public function __construct(
        protected readonly ImportHttpClient $httpClient,
    ) {
    }

    /**
     * Null when the download yields no usable bytes.
     *
     * @param string $apiKey       sent only to $apiDomain, which refuses anonymous requests
     * @param string|null $failureDetail out-param: extra diagnosis on failure,
     *        null when there is nothing worth saying beyond "it failed"
     * @param int|null $failureStatus out-param: HTTP status of a failed
     *        download, null when no response arrived. Decides whether the
     *        asset counts as removed upstream.
     */
    public function download(
        Folder $target,
        Folder $staging,
        string $downloadUrl,
        string $apiKey = '',
        string $apiDomain = '',
        ?string &$failureDetail = null,
        ?int &$failureStatus = null,
    ): ?File {
        $failureDetail = null;
        $failureStatus = null;
        $fileName = $this->buildFileName($downloadUrl);

        // Promoted by an earlier successful run — reuse, don't re-download.
        if ($target->hasFile($fileName)) {
            $existing = $target->getFile($fileName);
            return $existing instanceof File ? $existing : null;
        }

        // Already staged this run (same image shared across POIs).
        if ($staging->hasFile($fileName)) {
            $existing = $staging->getFile($fileName);
            return $existing instanceof File ? $existing : null;
        }

        $contents = $this->fetchContents(
            $this->authenticate($downloadUrl, $apiKey, $apiDomain),
            $failureDetail,
            $failureStatus
        );
        if ($contents === null || $contents === '') {
            return null;
        }

        $file = $staging->createFile($fileName);
        $file->setContents($contents);

        return $file;
    }

    /**
     * The API host refuses anonymous requests. Keyed on host, not on caller.
     */
    protected function authenticate(string $downloadUrl, string $apiKey, string $apiDomain): string
    {
        if ($apiKey === '' || $apiDomain === '') {
            return $downloadUrl;
        }

        $assetHost = parse_url($downloadUrl, PHP_URL_HOST);
        $apiHost = parse_url($apiDomain, PHP_URL_HOST) ?: $apiDomain;
        if (!is_string($assetHost) || $assetHost !== $apiHost) {
            return $downloadUrl;
        }

        $separator = str_contains($downloadUrl, '?') ? '&' : '?';

        return $downloadUrl . $separator . http_build_query(['api_key' => $apiKey]);
    }

    /**
     * A failed download is data drift, not a run-ending fault, so it is reported
     * by returning null rather than by raising.
     *
     * @param int|null $status out-param: HTTP status where there was one,
     *        null when no response arrived at all.
     */
    protected function fetchContents(
        string $downloadUrl,
        ?string &$failureDetail = null,
        ?int &$status = null
    ): ?string {
        $status = null;

        $url = $downloadUrl;

        // PSR-18 forbids sendRequest() from following redirects, so walk the hops.
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            // Built directly, not via Import\RequestFactory: that one appends
            // format=jsonld, which an image URL must not carry.
            $request = new Request($url, 'GET');

            try {
                $response = $this->httpClient->sendRequest($request);
            } catch (RetryExhaustedException $exhausted) {
                $failureDetail = sprintf('gave up after %d attempts', $exhausted->attempts);
                return null;
            } catch (ClientExceptionInterface) {
                // No status at all — DNS, refused connection, timeout.
                return null;
            }

            $status = $response->getStatusCode();

            if ($status >= 300 && $status < 400) {
                $location = trim($response->getHeaderLine('location'));
                if ($location === '') {
                    $failureDetail = sprintf('redirect %d carried no location', $status);
                    return null;
                }

                $url = $this->resolveLocation($url, $location);
                continue;
            }

            if ($status !== 200) {
                $attempts = (int)$response->getHeaderLine(RetryingClient::ATTEMPTS_HEADER);
                if ($attempts > 1) {
                    $failureDetail = sprintf('gave up after %d attempts', $attempts);
                }
                return null;
            }

            return (string)$response->getBody();
        }

        $failureDetail = sprintf('more than %d redirects', self::MAX_REDIRECTS);
        $status = null;

        return null;
    }

    /** A Location may be absolute, root-relative or path-relative. */
    protected function resolveLocation(string $currentUrl, string $location): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'https';
        $host = is_string($parts['host'] ?? null) ? $parts['host'] : '';
        if ($host === '') {
            return $location;
        }

        $port = is_int($parts['port'] ?? null) ? ':' . $parts['port'] : '';
        $base = $scheme . '://' . $host . $port;

        if (str_starts_with($location, '/')) {
            return $base . $location;
        }

        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '/';

        return $base . substr($path, 0, (int)strrpos($path, '/') + 1) . $location;
    }

    /** Identity is the download URL; editorial text must not reach the name. */
    protected function buildFileName(string $downloadUrl): string
    {
        $path = (string)(parse_url($downloadUrl, PHP_URL_PATH) ?: '');

        // jpg only as a last resort — png and webp are just as likely.
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'jpg';
        }

        // Stem for browsability only; upstream stems are generic, so the hash separates.
        $stem = (string)preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($path, PATHINFO_FILENAME));
        $stem = trim($stem, '-.');
        $hash = substr(hash('sha256', $downloadUrl), 0, 16);

        return ($stem === '' ? $hash : $stem . '_' . $hash) . '.' . $extension;
    }
}
