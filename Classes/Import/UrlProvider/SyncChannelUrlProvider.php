<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
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
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannel;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncWindow;

class SyncChannelUrlProvider implements UrlProvider
{
    protected int $configurationUid = 0;

    protected int $storagePid = 0;

    protected string $channelId = '';

    protected string $syncHost = '';

    public function __construct(
        protected readonly SyncChannel $syncChannel,
        protected readonly SyncWindow $syncWindow,
    ) {
    }

    public function canProvideForConfiguration(ImportConfigurationInterface $configuration): bool
    {
        return $configuration->getType() === 'syncChannel';
    }

    public function createWithConfiguration(ImportConfigurationInterface $configuration): UrlProvider
    {
        if (!$configuration instanceof ImportConfiguration) {
            throw new InvalidArgumentException('Received incompatible import configuration.', 1792394051);
        }

        $instance = clone $this;
        $instance->configurationUid = (int)$configuration->getUid();
        $instance->storagePid = $configuration->getStoragePid();
        $instance->channelId = $configuration->getSyncChannelId();
        $instance->syncHost = $configuration->getSyncApiDomain();

        return $instance;
    }

    public function getUrls(): array
    {
        return $this->getTargets()['import'];
    }

    /**
     * Both lists of one read, so a job's work list never mixes two windows.
     *
     * @return array{import: list<string>, delete: list<string>} record URLs to import, remote IDs to delete
     */
    public function getTargets(): array
    {
        $lists = $this->syncChannel->read(
            $this->syncHost,
            $this->channelId,
            $this->syncWindow->since($this->configurationUid, $this->storagePid)
        );
        $recordUrl = SyncChannel::recordUrlPrefix($this->syncHost, $this->channelId);

        return [
            'import' => array_map(
                static fn (string $id): string => $recordUrl . rawurlencode($id),
                $lists->idsToImport
            ),
            'delete' => $lists->remoteIdsToDelete,
        ];
    }
}
