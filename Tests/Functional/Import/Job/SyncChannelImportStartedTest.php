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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\Job;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;

final class SyncChannelImportStartedTest extends AbstractSyncChannelImportTestCase
{
    #[Test]
    public function channelListsBecomeImportItemsFollowedByDeleteItems(): void
    {
        $this->expectChannelLists('list-changed-removed-with-import.json', 'list-expired-expired.json');

        $job = $this->startJob(1);

        $this->assertSame([
            [self::RECORD . 'test-event-1', 'import'],
            ['https://thuecat.org/resources/test-event-removed', 'delete'],
            ['https://thuecat.org/resources/test-event-expired', 'delete'],
        ], array_map(static fn (array $item): array => [$item['target'], $item['action']], $this->jobItems($job)));
    }
}
