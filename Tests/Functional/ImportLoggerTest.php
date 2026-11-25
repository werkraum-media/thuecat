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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\ImportLogger;

/**
 * Covers the DataHandler error-capture pipeline that hangs off ImportLogger:
 * - recordDataHandlerErrors stages one tx_thuecat_import_log_entry row of
 *   type=dataHandlerError severity=error per captured errorLog line.
 */
final class ImportLoggerTest extends AbstractImportTestCase
{
    #[Test]
    public function recordsDataHandlerErrorsAsLogEntries(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        $logger->recordDataHandlerErrors([
            '[1.2]: Attempt to localize record on non-translatable table',
            '[1.1]: Some other complaint',
        ], 1);

        $logger->writeLogInto($logger->openLog(0), [], []);

        $rows = $this->fetchEntries();
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertSame('dataHandlerError', $row['type']);
            $this->assertSame('error', $row['severity']);
            $this->assertSame('{"iteration":1}', $row['context']);
            $this->assertNotSame('', $row['message']);
        }
    }

    #[Test]
    public function recordsExceptionsAsLogEntries(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        $logger->recordException(
            'fetchingError',
            new RuntimeException('upstream returned 500', 1700000001)
        );

        $logger->writeLogInto($logger->openLog(0), [], []);

        $rows = $this->fetchEntries();
        $this->assertCount(1, $rows);
        $this->assertSame('fetchingError', $rows[0]['type']);
        $this->assertSame('error', $rows[0]['severity']);
        $this->assertSame('upstream returned 500', $rows[0]['message']);
    }

    #[Test]
    public function reportSurfacesRecordedErrorsThroughTheHydratedLog(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        $logger->recordDataHandlerErrors(['[1.2]: DataHandler refused the row'], 0);
        $logger->recordException(
            'fetchingError',
            new RuntimeException('upstream returned 500', 1700000001)
        );
        $logger->writeLogInto($logger->openLog(0), [], []);

        $log = $this->get(ImportLogRepository::class)->findAll()->getFirst();
        $this->assertInstanceOf(ImportLog::class, $log);

        $this->assertTrue($log->hasErrors());
        $errors = $log->getListOfErrors();
        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('DataHandler refused the row', implode("\n", $errors));
        $this->assertStringContainsString('upstream returned 500', implode("\n", $errors));
    }

    #[Test]
    public function recordsDataHandlerRefusalDuringImport(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsOrganizationIntoStandardPage.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importJob(1);

        $dataHandlerErrors = array_values(array_filter(
            $this->fetchEntries(),
            static fn (array $row): bool => $row['type'] === 'dataHandlerError'
        ));
        $this->assertNotSame([], $dataHandlerErrors);
        $this->assertSame('error', $dataHandlerErrors[0]['severity']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchEntries(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_thuecat_import_log_entry')
        ;
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from('tx_thuecat_import_log_entry')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }
}
