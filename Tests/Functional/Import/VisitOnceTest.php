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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * A record already visited in a run is related, not fetched again. The fetch cache
 * would hide a repeat, so the run bypasses it; the faker then fails any second
 * request on its own.
 *
 * Two guards hold this, `isUpdated()` and the `remoteIdToKey` map; the test fails
 * only when both do, which is the contract rather than either mechanism.
 */
class VisitOnceTest extends AbstractImportTestCase
{
    private const TRAIL_ID = 'https://thuecat.org/resources/e_106954656-oatour';
    private const ATTRACTION_ID = 'https://thuecat.org/resources/347070073883-rqbn';

    protected string $fixtureGuzzleBase = __DIR__ . '/../Fixtures/Import/Guzzle';

    #[Test]
    public function aReferenceToARootAlreadyVisitedIsRelatedWithoutFetchingIt(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailThenAttractionInIt.php');
        $this->expectFetch('e_106954656-oatour.json');
        foreach ([
            '856934189528-xfec',
            '685822377106-mbtz',
            '055661589550-rnxb',
            '986455731991-nmbx',
            '916373333853-mknj',
            '887654277691-eatw',
            '192875159827-xfqk',
        ] as $keyword) {
            $this->expectFetch($keyword . '.json');
        }
        $this->expectFetchForUrl(
            self::ATTRACTION_ID,
            'thuecat.org/resources/attraction-in-trail-with-relations.json'
        );

        $this->importConfigurationBypassingCache(1);

        $trailUid = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL_ID);
        self::assertGreaterThan(0, $trailUid, 'The trail must be imported.');
        self::assertSame(
            (string)$trailUid,
            $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION_ID)['contained_in_trail'] ?? null,
            'The attraction relates the trail stored by the earlier root.'
        );
    }
}
