<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Backend;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\ReferenceSkipped;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;

class ImportLogTest extends TestCase
{
    #[Test]
    public function returnsConfigurationIfSet(): void
    {
        $configuration = new ImportConfiguration();
        $subject = new ImportLog($configuration);

        self::assertSame($configuration, $subject->getConfiguration());
    }

    #[Test]
    public function returnsNullForConfigurationIfNotSet(): void
    {
        $subject = new ImportLog();

        self::assertNull($subject->getConfiguration());
    }

    #[Test]
    public function returnsConfigurationUidIfSet(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('uid', 10);
        $subject = new ImportLog($configuration);

        self::assertSame(10, $subject->getConfigurationUid());
    }

    #[Test]
    public function returnsZeroForConfigurationIfNotSet(): void
    {
        $subject = new ImportLog();

        self::assertSame(0, $subject->getConfigurationUid());
    }

    #[Test]
    public function summaryOfEntriesSplitsInsertedAndUpdatedPerTable(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->savingEntry('tx_events_domain_model_event', true));
        $subject->addEntry($this->savingEntry('tx_events_domain_model_event', false));
        $subject->addEntry($this->savingEntry('tx_events_domain_model_event', false));
        $subject->addEntry($this->savingEntry('tx_thuecat_organisation', true));

        self::assertSame(
            [
                'tx_events_domain_model_event' => ['total' => 3, 'inserted' => 1, 'updated' => 2],
                'tx_thuecat_organisation' => ['total' => 1, 'inserted' => 1, 'updated' => 0],
            ],
            $subject->getSummaryOfEntries()
        );
    }

    #[Test]
    public function listOfErrorsSurfacesWarningsWhenThereAreNoErrors(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->entryWithSeverity('warning', 'remote-a', 'Skipped reference'));

        self::assertSame(
            ['Resource: remote-a Warning: Skipped reference'],
            $subject->getListOfErrors(),
            'A warning-only run showed an empty Errors column, so the referenceSkipped '
            . 'rows were invisible in the BE module — the only place their detail lives.'
        );
    }

    #[Test]
    public function listOfErrorsHidesWarningsWhenErrorsArePresent(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->entryWithSeverity('warning', 'remote-a', 'Skipped reference'));
        $subject->addEntry($this->entryWithSeverity('error', 'remote-b', 'Mapping failed'));

        self::assertSame(
            ['Resource: remote-b Error: Mapping failed'],
            $subject->getListOfErrors(),
            'Errors take precedence: a run with both shows only what blocks it.'
        );
    }

    #[Test]
    public function hasErrorsStaysFalseForWarningsOnly(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->entryWithSeverity('warning', 'remote-a', 'Skipped reference'));

        self::assertFalse(
            $subject->hasErrors(),
            'A warning must not mark the row as danger; it is not a failure.'
        );
    }

    #[Test]
    public function groupedNoticesCollectsNoticesAndWarningsByType(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->typedEntry('vocabularyStale', 'warning', '', 'Vocabulary is 20 days old'));
        $subject->addEntry($this->typedEntry('categoryWithoutHierarchy', 'notice', 'thuecat:Studio', 'Stays flat'));

        self::assertSame(
            [
                'vocabularyStale' => ['Vocabulary is 20 days old'],
                'categoryWithoutHierarchy' => ['thuecat:Studio: Stays flat'],
            ],
            $subject->getGroupedNotices(),
            'Notices and warnings both belong here, keyed by type so one noisy type '
            . 'cannot bury another.'
        );
    }

    #[Test]
    public function groupedNoticesSurvivesARunThatAlsoErrored(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->typedEntry('vocabularyStale', 'warning', '', 'Vocabulary is 20 days old'));
        $subject->addEntry($this->entryWithSeverity('error', 'remote-b', 'Mapping failed'));

        self::assertSame(
            ['vocabularyStale' => ['Vocabulary is 20 days old']],
            $subject->getGroupedNotices(),
            'Unlike getListOfErrors(), this column is not a fallback: an error elsewhere '
            . 'must not hide why the vocabulary was stale.'
        );
    }

    #[Test]
    public function groupedNoticesIgnoresDebugAndInfo(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->typedEntry('categoryParentChosen', 'debug', 'schema:Museum', 'Placed beneath Place'));
        $subject->addEntry($this->typedEntry('categoryMatched', 'info', 'schema:Museum', 'Matched'));

        self::assertSame(
            [],
            $subject->getGroupedNotices(),
            'Debug fires once per branching class per run; it would drown the column.'
        );
    }

    #[Test]
    public function groupedNoticesDeduplicatesRepeatedMessages(): void
    {
        $subject = new ImportLog();
        $subject->addEntry($this->typedEntry('categoryWithoutHierarchy', 'notice', 'thuecat:Studio', 'Stays flat'));
        $subject->addEntry($this->typedEntry('categoryWithoutHierarchy', 'notice', 'thuecat:Studio', 'Stays flat'));

        self::assertSame(
            ['categoryWithoutHierarchy' => ['thuecat:Studio: Stays flat']],
            $subject->getGroupedNotices(),
            'The same class recurs on every record carrying it.'
        );
    }

    private function typedEntry(
        string $type,
        string $severity,
        string $remoteId,
        string $message
    ): ImportLogEntry {
        $entry = new TypedLogEntry($type);
        $entry->_setProperty('severity', $severity);
        $entry->_setProperty('remoteId', $remoteId);
        $entry->_setProperty('message', $message);

        return $entry;
    }

    private function entryWithSeverity(string $severity, string $remoteId, string $message): ImportLogEntry
    {
        $entry = (new ReflectionClass(ReferenceSkipped::class))->newInstanceWithoutConstructor();
        $entry->_setProperty('severity', $severity);
        $entry->_setProperty('remoteId', $remoteId);
        $entry->_setProperty('message', $message);

        return $entry;
    }

    private function savingEntry(string $table, bool $insertion): SavingEntity
    {
        // Bypass the entity-dependent constructor; set the DB-hydrated fields directly.
        $entry = (new ReflectionClass(SavingEntity::class))->newInstanceWithoutConstructor();
        $entry->_setProperty('tableName', $table);
        $entry->_setProperty('insertion', $insertion ? 1 : 0);

        return $entry;
    }
}
