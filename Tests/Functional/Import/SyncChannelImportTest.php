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

        $request = GuzzleClientFaker::getRequestForUrl(self::RECORD . 'test-event-1');
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
        $this->expectChannelRecords('test-event-1');

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
            [$this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1')],
            $this->fetchMmRelatedUids(
                'tx_thuecat_tourist_attraction',
                $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', 'https://thuecat.org/resources/test-place-1'),
                'hosts_events'
            )
        );
    }

    #[Test]
    public function referenceOutsideTheChannelFallsBackToItsCarriedUrlWithoutLogging(): void
    {
        $this->expectChannelServing('list-changed-keyword.json', 'test-event-keyword-1');
        $this->expectKeywordOutsideTheChannel();

        $this->importConfiguration(1);

        self::assertSame([], $this->getLogEntriesOfType('referenceSkipped'));
        self::assertSame([], $this->getLogEntriesOfType('fetchingError'));
    }

    #[Test]
    public function referenceFetchedFromItsCarriedUrlKeepsThatUrlAsIdentity(): void
    {
        $this->expectChannelServing('list-changed-keyword.json', 'test-event-keyword-1');
        $this->expectKeywordOutsideTheChannel();

        $this->importConfiguration(1);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('sys_category', 'keyword:https://thuecat.org/resources/916373333853-mknj'));
    }

    #[Test]
    public function referenceSharedByTwoEventsIsAskedOfTheChannelOnce(): void
    {
        $this->expectChannelServing('list-changed-media-outside-channel.json', 'test-event-media-2a', 'test-event-media-2b');
        $this->expectFailureForUrl(self::RECORD . 'test-media-2', 200);
        $this->expectFetchForUrl('https://thuecat.org/resources/test-media-2', 'thuecat.org/resources/test-media-2.json');

        $this->importConfiguration(1);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-media-2a'));
        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-media-2b'));
    }

    #[Test]
    public function runOfAnotherTypeNeverAsksAChannel(): void
    {
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/test-event-keyword-1',
            'widgets.thuecat.org/api/sync/test-channel/jsonld/test-event-keyword-1.json'
        );
        $this->expectFetchForUrl('https://thuecat.org/resources/916373333853-mknj', 'thuecat.org/resources/916373333853-mknj.json');
        $this->expectFetchForUrl('https://thuecat.org/resources/192875159827-xfqk', 'thuecat.org/resources/192875159827-xfqk.json');
        $this->expectFetchForUrl(self::PLACE, self::PLACE_FIXTURE);

        $this->importConfiguration(2);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('sys_category', 'keyword:https://thuecat.org/resources/916373333853-mknj'));
    }

    // The keyword and the term set it belongs to.
    protected function expectKeywordOutsideTheChannel(): void
    {
        foreach (['916373333853-mknj', '192875159827-xfqk'] as $id) {
            $this->expectFailureForUrl(self::RECORD . $id, 200);
            $this->expectFetchForUrl('https://thuecat.org/resources/' . $id, 'thuecat.org/resources/' . $id . '.json');
        }
    }
}
