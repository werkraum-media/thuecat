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

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;

final class SyncChannelImportTest extends AbstractSyncChannelImportTestCase
{
    #[Test]
    public function unreadableExpiryListFailsTheRunBeforeAnyRecordIsFetched(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed.json');
        $this->expectFailureForUrl(self::LIST_EXPIRED, 403);

        try {
            $this->importConfiguration(1);
            self::fail('The run went on without the expiry list.');
        } catch (InvalidResponseException) {
        }

        self::assertCount(1, $this->getLogEntriesOfType('runFailed'));
    }

    #[Test]
    public function importsAListedEventUnderItsOwnId(): void
    {
        $this->expectChannelServingOneEvent();

        $this->importConfiguration(1);

        $this->assertCSVDataSet(__DIR__ . '/Assertions/SyncChannelImportsListedEvent.csv');
    }

    #[Test]
    public function asksTheChannelWithTheSyncKeyAsHeaderOnly(): void
    {
        $this->expectChannelServingOneEvent();

        $this->importConfiguration(1);

        $request = GuzzleClientFaker::getLastRequest();
        self::assertNotNull($request);
        self::assertSame(self::RECORD . 'test-event-1', (string)$request->getUri());
        self::assertSame('APIKEY test-sync-key', $request->getHeaderLine('Authorization'));
    }

    #[Test]
    public function unservedListedEventIsAFetchingErrorAndTheNextIsImported(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-with-unserved.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectFailureForUrl(self::RECORD . 'test-event-unserved', 200);
        $this->expectFetchForUrl(self::RECORD . 'test-event-1', 'widgets.thuecat.org/api/sync/test-channel/jsonld/test-event-1.json');

        $this->importConfiguration(1);

        $errors = $this->getLogEntriesOfType('fetchingError');
        self::assertCount(1, $errors);
        self::assertIsString($errors[0]['message']);
        self::assertStringContainsString(self::RECORD . 'test-event-unserved', $errors[0]['message']);
        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1'));
    }

    #[Test]
    public function importedEventKeepsTheResourceHostAsSource(): void
    {
        $this->expectChannelServingOneEvent();

        $this->importConfiguration(1);

        self::assertSame(
            'https://thuecat.org',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1')['source_url']
        );
    }

    #[Test]
    public function importedEventIsRelatedToTheReferencedPlaceOfTheSite(): void
    {
        $this->expectChannelServingOneEvent();

        $this->importConfiguration(1);

        self::assertSame(
            (string)$this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1'),
            $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', 'https://thuecat.org/resources/test-place-1')['hosts_events']
        );
    }
}
