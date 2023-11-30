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

use InvalidArgumentException;
use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;

class SyncScopeUrlProvider implements UrlProvider
{
    /**
     * @var FetchData
     */
    private $fetchData;

    /**
     * @var string
     */
    private $syncScopeId = '';

    public function __construct(
        FetchData $fetchData
    ) {
        $this->fetchData = $fetchData;
    }

    public function canProvideForConfiguration(
        ImportConfiguration $configuration
    ): bool {
        return $configuration->getType() === 'syncScope';
    }

    public function createWithConfiguration(
        ImportConfiguration $configuration
    ): UrlProvider {
        if (method_exists($configuration, 'getSyncScopeId') === false) {
            throw new InvalidArgumentException('Received incompatible import configuration.', 1629709276);
        }
        $instance = clone $this;
        $instance->syncScopeId = $configuration->getSyncScopeId();

        return $instance;
    }

    public function getUrls(): array
    {
        $response = $this->fetchData->updatedNodes($this->syncScopeId);
        $resourceIds = array_values($response['data']['createdOrUpdated'] ?? []);

        return array_map(function (string $id) {
            return $this->fetchData->getFullResourceUrl($id);
        }, $resourceIds);
    }
}
