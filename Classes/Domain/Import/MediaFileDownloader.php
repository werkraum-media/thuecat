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

namespace WerkraumMedia\ThueCat\Domain\Import;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Downloads a single POI media file from its source URL into the import's FAL
 * staging folder and returns the resulting sys_file. Files are named by their
 * stable ThueCat dms id (plus a sanitised original name) so re-imports dedupe:
 * an already-present file — in the target folder from a prior run, or in
 * staging from this run — is reused instead of fetched again.
 */
#[Autoconfigure(public: true)]
class MediaFileDownloader
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {
    }

    /**
     * Return the file for $dmsId/$originalName, downloading it into $staging
     * when neither the target nor staging already holds it. Returns null when
     * the download yields no usable bytes.
     *
     * @param string $dmsId        stable ThueCat resource id, e.g. "dms_5159216"
     * @param string $originalName source filename incl. extension, e.g. "Foo.jpg"
     */
    public function download(
        Folder $target,
        Folder $staging,
        string $downloadUrl,
        string $dmsId,
        string $originalName,
    ): ?File {
        $fileName = $this->buildFileName($dmsId, $originalName);

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

        $contents = $this->fetchContents($downloadUrl);
        if ($contents === null || $contents === '') {
            return null;
        }

        $file = $staging->createFile($fileName);
        $file->setContents($contents);

        return $file;
    }

    private function fetchContents(string $downloadUrl): ?string
    {
        $response = $this->requestFactory->request($downloadUrl);
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return (string)$response->getBody();
    }

    private function buildFileName(string $dmsId, string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
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
