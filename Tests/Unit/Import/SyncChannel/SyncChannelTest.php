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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SyncChannel;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannel;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelLists;

final class SyncChannelTest extends TestCase
{
    private const LIST_CHANGED = 'https://widgets.thuecat.org/api/sync/test-channel/list-changed?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    private const LIST_EXPIRED = 'https://widgets.thuecat.org/api/sync/test-channel/list-expired?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    /**
     * @var list<string>
     */
    protected array $requestedUrls = [];

    #[Test]
    public function importsChangedIdsNeitherRemovedNorExpiredInListOrder(): void
    {
        $subject = $this->subjectAnswering([
            self::LIST_CHANGED => [
                'createdOrUpdated' => ['test-event-3', 'test-event-1', 'test-event-2', 'test-event-4'],
                'removed' => ['test-event-2'],
            ],
            self::LIST_EXPIRED => ['expired' => ['test-event-4']],
        ]);

        self::assertSame(
            ['test-event-3', 'test-event-1'],
            $this->read($subject)->idsToImport
        );
    }

    #[Test]
    public function deletesRemovedAndExpiredIdsAsResourceUrls(): void
    {
        $subject = $this->subjectAnswering([
            self::LIST_CHANGED => [
                'createdOrUpdated' => ['test-event-1'],
                'removed' => ['test-event-2'],
            ],
            self::LIST_EXPIRED => ['expired' => ['test-event-4', 'test-event-2']],
        ]);

        self::assertSame(
            ['https://thuecat.org/resources/test-event-2', 'https://thuecat.org/resources/test-event-4'],
            $this->read($subject)->remoteIdsToDelete
        );
    }

    #[Test]
    public function followsPageTokensUntilNoneIsReturned(): void
    {
        $subject = $this->subjectAnswering([
            self::LIST_CHANGED => [
                'createdOrUpdated' => ['test-event-1'],
                'removed' => ['test-event-2'],
                'pageToken' => 'test-changed-page-2',
            ],
            self::LIST_CHANGED . '&pageToken=test-changed-page-2' => [
                'createdOrUpdated' => ['test-event-3'],
                'removed' => ['test-event-4'],
            ],
            self::LIST_EXPIRED => [
                'expired' => ['test-event-5'],
                'pageToken' => 'test-expired-page-2',
            ],
            self::LIST_EXPIRED . '&pageToken=test-expired-page-2' => ['expired' => ['test-event-6']],
        ]);

        $lists = $this->read($subject);

        self::assertSame(['test-event-1', 'test-event-3'], $lists->idsToImport);
        self::assertSame(
            [
                'https://thuecat.org/resources/test-event-2',
                'https://thuecat.org/resources/test-event-4',
                'https://thuecat.org/resources/test-event-5',
                'https://thuecat.org/resources/test-event-6',
            ],
            $lists->remoteIdsToDelete
        );
    }

    #[Test]
    public function readsBothListsOnceWithTheSameSince(): void
    {
        $subject = $this->subjectAnswering([
            self::LIST_CHANGED => ['createdOrUpdated' => [], 'removed' => []],
            self::LIST_EXPIRED => ['expired' => []],
        ]);

        $this->read($subject);

        self::assertSame([self::LIST_CHANGED, self::LIST_EXPIRED], $this->requestedUrls);
    }

    protected function read(SyncChannel $subject): SyncChannelLists
    {
        return $subject->read(
            'https://widgets.thuecat.org',
            'test-channel',
            new DateTimeImmutable('2026-10-14 03:00', new DateTimeZone('Europe/Berlin'))
        );
    }

    /**
     * @param array<string, array<string, mixed>> $responses Keyed by the exact URL requested.
     */
    protected function subjectAnswering(array $responses): SyncChannel
    {
        $fetchData = $this->getMockBuilder(FetchData::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['jsonLDFromUrlIgnoringCache', 'jsonLDFromUrl'])
            ->getMock()
        ;
        $fetchData->expects(self::never())->method('jsonLDFromUrl');
        $fetchData->method('jsonLDFromUrlIgnoringCache')->willReturnCallback(
            function (string $url) use ($responses): array {
                $this->requestedUrls[] = $url;
                return $responses[$url] ?? throw new RuntimeException('Unexpected request: ' . $url, 1792404001);
            }
        );

        return new SyncChannel($fetchData);
    }
}
