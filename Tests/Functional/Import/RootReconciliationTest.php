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
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class RootReconciliationTest extends AbstractImportTestCase
{
    private const string TRAIL = 'https://thuecat.org/resources/e_106954656-oatour';

    private const string ORGANISATION = 'https://thuecat.org/resources/018132452787-ngbe';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/StoredOrganisation.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/StoredTrailManagedByOrganisation.csv');
        $this->expectFetch('887654277691-eatw.json', '192875159827-xfqk.json');
    }

    // Only a root's own node is complete; a reference is never judged by what it carried this time.
    #[Test]
    public function organisationReachedAsAReferenceKeepsItsValues(): void
    {
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-sharing-organisation-and-keyword.json');
        $this->expectFetchForUrl(self::ORGANISATION, 'thuecat.org/resources/organisation-without-description.json');

        $this->importJob(1);

        $this->assertSame('Stored description', $this->fetchRowByRemoteId('tx_thuecat_organisation', self::ORGANISATION)['description']);
    }

    #[Test]
    public function managingOrganisationDroppedUpstreamIsUnrelatedAndKept(): void
    {
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-without-organisation.json');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchRowByRemoteId('tx_thuecat_trail', self::TRAIL)['managed_by']);
        $this->assertSame(1, $this->countRows('tx_thuecat_organisation'));
    }

    // Data drift, not a removal: the reference still exists, the field cannot hold it.
    #[Test]
    public function unrelatableManagingReferenceKeepsTheStoredOrganisation(): void
    {
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-managed-by-a-town.json');
        $this->expectFetch('town-managed-by-translated-organisation.json');

        $this->importJob(1);

        $this->assertSame(1, $this->fetchRowByRemoteId('tx_thuecat_trail', self::TRAIL)['managed_by']);
        $this->assertSame('managed_by', $this->logEntryContext('referenceUnrelatable')['field']);
    }

    #[Test]
    public function failedFetchOfTheManagingOrganisationKeepsIt(): void
    {
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-sharing-organisation-and-keyword.json');
        $this->expectFailureForUrl(self::ORGANISATION, 404);

        $this->importJob(1);

        $this->assertSame(1, $this->fetchRowByRemoteId('tx_thuecat_trail', self::TRAIL)['managed_by']);
    }

    #[Test]
    public function removalIsLoggedWithRootFieldAndValue(): void
    {
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-without-organisation.json');

        $this->importJob(1);

        $entries = $this->getLogEntriesOfType('removedUpstream');
        $this->assertSame(
            [['tx_thuecat_trail', self::TRAIL, '{"field":"managed_by","value":1}']],
            array_map(static fn (array $entry): array => [$entry['table_name'], $entry['remote_id'], $entry['context']], $entries)
        );
        $log = $this->get(ImportLogRepository::class)->findAll()->getFirst();
        $this->assertInstanceOf(ImportLog::class, $log);
        $this->assertSame(
            ['removedUpstream'],
            array_values(array_filter(
                array_map(static fn (ImportLogEntry $entry): string => $entry->getType(), iterator_to_array($log->getEntries(), false)),
                static fn (string $type): bool => $type === 'removedUpstream'
            ))
        );
    }
}
