<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;

/** A struggling upstream must be distinguishable from a healthy one. */
class RecoveredRetryTest extends AbstractImportTestCase
{
    #[Test]
    public function aRecoveredRequestIsRecorded(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        // Consumed FIFO: the 503 is attempt one, the fixture is attempt two.
        $this->expectFailure('018132452787-ngbe', 503, 'Service Unavailable');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('retriesRecovered');

        self::assertCount(1, $entries, 'One summary entry per run, not one per request.');
        self::assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        self::assertIsArray($context);
        self::assertSame(1, $context['recoveredRequests'] ?? null);
        self::assertSame(1, $context['wastedAttempts'] ?? null);
    }

    /**
     * `notice` outranks a clean run's `debug`, so the recovery is visible in the
     * run summary — but it sits below `warning`, which is where
     * ImportLogger::isFailureSeverity() starts caring, so the exit code is a
     * clean run's.
     */
    #[Test]
    public function aRecoveredRequestIsNoticedButDoesNotFailTheRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFailure('018132452787-ngbe', 503, 'Service Unavailable');
        $this->expectFetch('018132452787-ngbe.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('notice', $severity);
        self::assertSame(
            'notice',
            $this->getLogEntriesOfType('retriesRecovered')[0]['severity'] ?? null,
            'A resource that was fetched is not a failure.'
        );
    }

    /**
     * Asserts the imported record, not the whole database: the shared assertion
     * dataset is used by runs that record no recovery, so it cannot carry the
     * extra log row this test expects.
     */
    #[Test]
    public function aRecoveredRequestStillImportsNormally(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFailure('018132452787-ngbe', 503, 'Service Unavailable');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $organisations = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_organisation')
            ->select(['remote_id', 'title'], 'tx_thuecat_organisation', [])
            ->fetchAllAssociative()
        ;

        self::assertSame(
            [[
                'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
                'title' => 'Erfurt Tourismus und Marketing GmbH',
            ]],
            $organisations,
            'The retry cost attempts, not records.'
        );
    }

    #[Test]
    public function aCleanRunRecordsNoRecoveryEntry(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        self::assertSame(
            [],
            $this->getLogEntriesOfType('retriesRecovered'),
            'Presence of the entry is the signal, so a clean run must write none.'
        );
    }
}
