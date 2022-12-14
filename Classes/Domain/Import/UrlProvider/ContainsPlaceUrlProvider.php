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

namespace WerkraumMedia\ThueCat\Domain\Import\UrlProvider;

use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;

class ContainsPlaceUrlProvider implements UrlProvider
{
    /**
     * @var FetchData
     */
    private $fetchData;

    /**
     * @var string
     */
    private $containsPlaceId = '';

    public function __construct(
        FetchData $fetchData
    ) {
        $this->fetchData = $fetchData;
    }

    public function canProvideForConfiguration(
        ImportConfiguration $configuration
    ): bool {
        return $configuration->getType() === 'containsPlace';
    }

    public function createWithConfiguration(
        ImportConfiguration $configuration
    ): UrlProvider {
        if (method_exists($configuration, 'getContainsPlaceId') === false) {
            throw new \InvalidArgumentException('Received incompatible import configuration.', 1629709276);
        }
        $instance = clone $this;
        $instance->containsPlaceId = $configuration->getContainsPlaceId();

        return $instance;
    }

    public function getUrls(): array
    {
        $response = $this->fetchData->jsonLDFromUrl(
            $this->fetchData->getFullResourceUrl($this->containsPlaceId)
        );
        $resources = array_values($response['@graph'][0]['schema:containsPlace'] ?? []);

        return array_map(function (array $resource) {
            return $resource['@id'] ?? '';
        }, $resources);
    }
}
