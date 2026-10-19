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

namespace WerkraumMedia\ThueCat\Import\SyncChannel;

use DateTimeImmutable;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;

/**
 * Reads a channel's change and expiry lists once per run and turns them into
 * the IDs to import and the remote IDs to delete. Expired events stay in the
 * change list, so the change list alone would re-import them.
 */
class SyncChannel
{
    public function __construct(
        protected readonly FetchData $fetchData,
    ) {
    }

    /**
     * Prefix of a single record's URL; the record ID is appended URL-encoded.
     */
    public static function recordUrlPrefix(string $syncHost, string $channelId): string
    {
        return rtrim($syncHost, '/') . '/api/sync/' . rawurlencode($channelId) . '/jsonld/';
    }

    public function read(string $syncHost, string $channelId, DateTimeImmutable $since): SyncChannelLists
    {
        $listUrl = rtrim($syncHost, '/') . '/api/sync/' . rawurlencode($channelId) . '/';
        $query = ['since' => $since->format(DATE_ATOM)];

        $changed = [];
        $removed = [];
        foreach ($this->pages($listUrl . 'list-changed', $query) as $page) {
            $changed = [...$changed, ...$this->idsIn($page, 'createdOrUpdated')];
            $removed = [...$removed, ...$this->idsIn($page, 'removed')];
        }
        $expired = [];
        foreach ($this->pages($listUrl . 'list-expired', $query) as $page) {
            $expired = [...$expired, ...$this->idsIn($page, 'expired')];
        }

        $gone = array_values(array_unique([...$removed, ...$expired]));

        return new SyncChannelLists(
            array_values(array_diff(array_values(array_unique($changed)), $gone)),
            array_map(fn (string $id): string => $this->fetchData->getFullResourceUrl($id), $gone),
        );
    }

    /**
     * @param array<string, string> $query
     *
     * @return iterable<array<mixed>>
     */
    protected function pages(string $url, array $query): iterable
    {
        do {
            $page = $this->fetchData->jsonLDFromUrlIgnoringCache($url . '?' . http_build_query($query));
            yield $page;
            $pageToken = $page['pageToken'] ?? null;
            $query['pageToken'] = is_string($pageToken) ? $pageToken : '';
        } while ($query['pageToken'] !== '');
    }

    /**
     * @param array<mixed> $page
     *
     * @return list<string>
     */
    protected function idsIn(array $page, string $key): array
    {
        $ids = $page[$key] ?? [];
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_filter($ids, is_string(...)));
    }
}
