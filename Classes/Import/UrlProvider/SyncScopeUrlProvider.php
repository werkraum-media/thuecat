<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

class SyncScopeUrlProvider implements UrlProvider
{
    protected string $syncScopeId = '';
    protected string $apiKey = '';

    protected int $fetchLastXDays = 0;

    public function __construct(
        protected readonly FetchData $fetchData
    ) {
    }

    public function canProvideForConfiguration(
        ImportConfigurationInterface $configuration
    ): bool {
        return $configuration->getType() === 'syncScope';
    }

    public function createWithConfiguration(
        ImportConfigurationInterface $configuration
    ): UrlProvider {
        if (method_exists($configuration, 'getSyncScopeId') === false) {
            throw new InvalidArgumentException('Received incompatible import configuration.', 1629709276);
        }
        $syncScopeId = $configuration->getSyncScopeId();
        $instance = clone $this;
        $instance->syncScopeId = is_string($syncScopeId) ? $syncScopeId : '';
        $instance->apiKey = $configuration->getApiKey();
        $instance->fetchLastXDays = $configuration->getFetchLastXDays();

        return $instance;
    }

    public function getUrls(?string $apiDomain = null): array
    {
        $response = $this->fetchData->updatedNodes($this->syncScopeId, $this->apiKey, $apiDomain, $this->fetchLastXDays);
        $resourceIds = array_values($response['data']['createdOrUpdated'] ?? []);

        return array_map(function (string $id) use ($apiDomain) {
            return $this->fetchData->getFullResourceUrl($id, $apiDomain);
        }, $resourceIds);
    }
}
