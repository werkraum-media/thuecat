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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;

/**
 * Ordinals count every request of the run: the change list is the first, the
 * expiry list the second.
 */
final class SyncChannelRateLimitTest extends AbstractSyncChannelImportTestCase
{
    #[Test]
    public function rateLimitedRootIsRecordedAndTheRunContinues(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-with-unserved.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectFailureForUrl(self::RECORD . 'test-event-unserved', 429);
        $this->expectChannelRecords('test-event-1');

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('rateLimited');
        $this->assertCount(1, $entries);
        $this->assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        $this->assertIsArray($context);
        $this->assertSame(self::RECORD . 'test-event-unserved', $context['url'] ?? null);
        $this->assertSame('widgets.thuecat.org', $context['host'] ?? null);
        $this->assertSame(3, $context['ordinal'] ?? null);
        $this->assertIsString($context['time'] ?? null);
        $this->assertNotFalse(DateTimeImmutable::createFromFormat(DATE_ATOM, $context['time']));

        $fetchingErrors = $this->getLogEntriesOfType('fetchingError');
        $this->assertCount(1, $fetchingErrors);
        $this->assertSame(self::RECORD . 'test-event-unserved', $fetchingErrors[0]['remote_id']);
        $this->assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1'));
    }

    #[Test]
    public function rateLimitedReferenceIsRecordedAndSkipped(): void
    {
        $this->expectChannelServing('list-changed-keyword.json', 'test-event-keyword-1');
        $this->expectFailureForUrl(self::RECORD . '916373333853-mknj', 200);
        $this->expectFailureForUrl('https://thuecat.org/resources/916373333853-mknj', 429);

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('rateLimited');
        $this->assertCount(1, $entries);
        $this->assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        $this->assertIsArray($context);
        $this->assertSame('https://thuecat.org/resources/916373333853-mknj?format=jsonld', $context['url'] ?? null);
        $this->assertSame('thuecat.org', $context['host'] ?? null);

        $skipped = $this->getLogEntriesOfType('referenceSkipped');
        $this->assertCount(1, $skipped);
        $this->assertIsString($skipped[0]['message']);
        $this->assertStringContainsString('https://thuecat.org/resources/916373333853-mknj', $skipped[0]['message']);
    }

    #[Test]
    public function twoRateLimitsInOneRunCarryDistinctOrdinals(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-media-outside-channel.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectFailureForUrl(self::RECORD . 'test-event-media-2a', 429);
        $this->expectFailureForUrl(self::RECORD . 'test-event-media-2b', 429);

        $this->importConfiguration(1);

        $ordinals = [];
        foreach ($this->getLogEntriesOfType('rateLimited') as $entry) {
            $this->assertIsString($entry['context']);
            $context = json_decode($entry['context'], true);
            $this->assertIsArray($context);
            $ordinals[] = $context['ordinal'] ?? null;
        }
        $this->assertSame([3, 4], $ordinals);
    }

    // The faker serves each staged response once: a retry would find none left.
    #[Test]
    public function rateLimitedRequestIsSentOnce(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectFailureForUrl(self::RECORD . 'test-event-1', 429);

        $this->importConfiguration(1);

        $this->assertCount(1, $this->getLogEntriesOfType('fetchingError'));
    }
}
