<?php

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

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;

use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData\InvalidResponseException;

class Media
{
    /**
     * @var FetchData
     */
    private $fetchData;

    public function __construct(
        FetchData $fetchData
    ) {
        $this->fetchData = $fetchData;
    }

    public function get(array $jsonLD): array
    {
        $media = [];

        $media = $this->addMainImage($jsonLD, $media);
        $media = $this->addSingleImage($jsonLD, $media);
        $media = $this->addImages($jsonLD, $media);

        return $media;
    }

    private function addMainImage(array $jsonLD, array $media): array
    {
        if (isset($jsonLD['schema:photo']['@id']) === false) {
            return $media;
        }

        try {
            $media[] = array_merge(
                [
                    'mainImage' => true,
                ],
                $this->getMedia($jsonLD['schema:photo']['@id'])
            );
        } catch (InvalidResponseException $e) {
            // Nothing todo
        }

        return $media;
    }

    private function addSingleImage(array $jsonLD, array $media): array
    {
        if (isset($jsonLD['schema:image']['@id']) === false) {
            return $media;
        }

        try {
            $media[] = array_merge(
                [
                    'mainImage' => false,
                ],
                $this->getMedia($jsonLD['schema:image']['@id'])
            );
        } catch (InvalidResponseException $e) {
            // Nothing todo
        }

        return $media;
    }

    private function addImages(array $jsonLD, array $media): array
    {
        if (
            isset($jsonLD['schema:image']) === false
            || isset($jsonLD['schema:image']['@id'])
            || is_array($jsonLD['schema:image']) === false
        ) {
            return $media;
        }

        foreach ($jsonLD['schema:image'] as $image) {
            try {
                $media[] = array_merge(
                    [
                        'mainImage' => false,
                    ],
                    $this->getMedia($image['@id'])
                );
            } catch (InvalidResponseException $e) {
                // Nothing todo
            }
        }

        return $media;
    }

    private function getMedia(string $resourceId): array
    {
        $jsonLD = $this->fetchData->jsonLDFromUrl($resourceId);
        $resource = $jsonLD['@graph'][0] ?? [];

        return [
            'type' => 'image',
            'title' => $resource['schema:name']['@value'] ?? '',
            'description' => $resource['schema:description']['@value'] ?? '',
            'url' => $resource['schema:url']['@value'] ?? '',
            'copyrightYear' => intval($resource['schema:copyrightYear']['@value'] ?? 0),
            'license' => [
                'type' => $resource['schema:license']['@value'] ?? '',
                'author' => $resource['thuecat:licenseAuthor']['@value'] ?? '',
            ],
        ];
    }
}
