<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Import\UrlProvider;

use InvalidArgumentException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;

class ContainsPlaceUrlProvider implements UrlProvider
{
    protected string $containsPlaceId = '';

    public function __construct(
        protected readonly FetchData $fetchData
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
