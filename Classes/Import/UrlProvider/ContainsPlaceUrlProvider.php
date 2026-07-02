<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Import\UrlProvider;

use InvalidArgumentException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;

class ContainsPlaceUrlProvider implements UrlProvider
{
    private string $containsPlaceId = '';

    public function __construct(
        private readonly FetchData $fetchData
    ) {
    }

    public function canProvideForConfiguration(
        ImportConfigurationInterface $configuration
    ): bool {
        return $configuration->getType() === 'containsPlace';
    }

    public function createWithConfiguration(
        ImportConfigurationInterface $configuration
    ): UrlProvider {
        if (method_exists($configuration, 'getContainsPlaceId') === false) {
            throw new InvalidArgumentException('Received incompatible import configuration.', 1629709276);
        }
        $containsPlaceId = $configuration->getContainsPlaceId();
        $instance = clone $this;
        $instance->containsPlaceId = is_string($containsPlaceId) ? $containsPlaceId : '';

        return $instance;
    }

    public function getUrls(?string $apiDomain = null): array
    {
        $response = $this->fetchData->jsonLDFromUrl(
            $this->fetchData->getFullResourceUrl($this->containsPlaceId, $apiDomain)
        );
        $containsPlace = $response['@graph'][0]['schema:containsPlace'] ?? [];
        $resources = is_array($containsPlace) ? array_values($containsPlace) : [];

        return array_map(static function (mixed $resource): string {
            $id = is_array($resource) ? ($resource['@id'] ?? '') : '';
            return is_string($id) ? $id : '';
        }, $resources);
    }
}
