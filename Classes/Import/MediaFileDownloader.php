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
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Downloads a single POI media file from its source URL into the import's FAL
 * staging folder and returns the resulting sys_file. Files are named by their
 * stable ThueCat dms id (plus a sanitised original name) so re-imports dedupe:
 * an already-present file — in the target folder from a prior run, or in
 * staging from this run — is reused instead of fetched again.
 *
 * Sends via the PSR-18 client, not RequestFactory::request(): a 4xx/5xx must
 * come back as a response so one unfetchable image can be skipped.
 */
#[Autoconfigure(public: true)]
class MediaFileDownloader
{
    public function __construct(
        protected readonly ClientInterface $httpClient,
    ) {
    }

    /**
     * Return the file for $dmsId/$originalName, downloading it into $staging
     * when neither the target nor staging already holds it. Returns null when
     * the download yields no usable bytes.
     *
     * @param string $dmsId        stable ThueCat resource id, e.g. "dms_5159216"
     * @param string $originalName source filename incl. extension, e.g. "Foo.jpg"
     * @param string $apiKey       sent only to $apiDomain, which refuses anonymous requests
     */
    public function download(
        Folder $target,
        Folder $staging,
        string $downloadUrl,
        string $dmsId,
        string $originalName,
        string $apiKey = '',
        string $apiDomain = '',
    ): ?File {
        $fileName = $this->buildFileName($dmsId, $originalName, $downloadUrl);

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

        $contents = $this->fetchContents($this->authenticate($downloadUrl, $apiKey, $apiDomain));
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
     */
    protected function fetchContents(string $downloadUrl): ?string
    {
        // Built directly, not via Import\RequestFactory: that one appends
        // format=jsonld, which an image URL must not carry.
        $request = new Request($downloadUrl, 'GET');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface) {
            // No status at all — DNS, refused connection, timeout.
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return (string)$response->getBody();
    }

    protected function buildFileName(string $dmsId, string $originalName, string $downloadUrl = ''): string
    {
        // The name upstream gave the file, else what the URL says it serves.
        // jpg only as a last resort — png and webp are just as likely.
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = strtolower(pathinfo(
                (string)(parse_url($downloadUrl, PHP_URL_PATH) ?: ''),
                PATHINFO_EXTENSION
            ));
        }
        if ($extension === '') {
            $extension = 'jpg';
        }
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        $base = (string)preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);
        $base = trim($base, '-');

        $name = $dmsId;
        if ($base !== '') {
            $name .= '_' . $base;
        }

        return $name . '.' . $extension;
    }
}
