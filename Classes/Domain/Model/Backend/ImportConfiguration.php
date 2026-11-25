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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

use DateTimeImmutable;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ImportConfiguration extends AbstractEntity implements ImportConfigurationInterface
{
    public const SYNC_API_DOMAIN = 'https://widgets.thuecat.org';

    protected string $title = '';

    protected string $type = '';

    protected ?DateTimeImmutable $tstamp = null;

    protected int $storagePid = 0;

    protected string $fileFolder = '';

    protected string $apiKey = '';

    protected string $syncScopeId = '';

    protected int $fetchLastXDays = 1;

    protected string $containsPlaceId = '';

    protected string $syncChannelId = '';

    protected int $messageBudget = 0;

    protected int $fetchCacheLifetime = 0;

    /**
     * @var ObjectStorage<ImportConfigurationUrl>
     */
    protected ObjectStorage $urls;

    /**
     * @var ObjectStorage<ImportLog>
     */
    protected ObjectStorage $logs;

    /**
     * @var string[]
     */
    protected array $allowedTypes = [];

    public function __construct()
    {
        $this->urls = new ObjectStorage();
        $this->logs = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTableName(): string
    {
        return 'tx_thuecat_import_configuration';
    }

    public function getLastChanged(): ?DateTimeImmutable
    {
        return $this->tstamp;
    }

    public function getLastImported(): ?DateTimeImmutable
    {
        $lastImport = null;
        $positionOfLastLog = count($this->logs) - 1;
        if ($this->logs->offsetExists($positionOfLastLog)) {
            $lastImport = $this->logs->offsetGet($positionOfLastLog);
        }

        if (!$lastImport instanceof ImportLog) {
            return null;
        }

        return $lastImport->getCreated();
    }

    public function getStoragePid(): int
    {
        return $this->storagePid;
    }

    public function getFileFolder(): string
    {
        return $this->fileFolder;
    }

    public function getUrls(): array
    {
        $urls = [];
        foreach ($this->urls as $url) {
            $urls[] = $url->getUrl();
        }

        return $urls;
    }

    /**
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getSyncScopeId(): string
    {
        return $this->syncScopeId;
    }

    public function getSyncChannelId(): string
    {
        return $this->syncChannelId;
    }

    public function getSyncApiDomain(): string
    {
        return self::SYNC_API_DOMAIN;
    }

    public function getFetchLastXDays(): int
    {
        return $this->fetchLastXDays;
    }

    public function getMessageBudget(): int
    {
        return $this->messageBudget;
    }

    public function getFetchCacheLifetime(): int
    {
        return $this->fetchCacheLifetime;
    }

    public function getContainsPlaceId(): string
    {
        return $this->containsPlaceId;
    }
}
