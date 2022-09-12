<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import;

use WerkraumMedia\ThueCat\Domain\Import\Import;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\Import
 */
class ImportTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new Import();

        self::assertInstanceOf(
            Import::class,
            $subject
        );
    }

    /**
     * @test
     */
    public function canStart(): void
    {
        $configuration = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration);

        self::assertSame(
            $configuration,
            $subject->getConfiguration()
        );
        self::assertInstanceOf(
            ImportLog::class,
            $subject->getLog()
        );
    }

    /**
     * @test
     */
    public function canEndAfterStart(): void
    {
        $configuration = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration);
        $subject->end();

        self::assertSame(
            $configuration,
            $subject->getConfiguration()
        );
        self::assertInstanceOf(
            ImportLog::class,
            $subject->getLog()
        );
    }

    /**
     * @test
     */
    public function isDoneAfterStartAndEnd(): void
    {
        $configuration = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration);
        $subject->end();

        self::assertTrue($subject->done());
    }

    /**
     * @test
     */
    public function isNotDoneAfterJustStartWithoutEnd(): void
    {
        $configuration = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration);

        self::assertFalse($subject->done());
    }

    /**
     * @test
     */
    public function nestedStartReturnsExpectedConfiguration(): void
    {
        $configuration1 = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration1);

        $configuration2 = new ImportConfiguration();
        $subject->start($configuration2);

        self::assertSame(
            $configuration2,
            $subject->getConfiguration()
        );
    }

    /**
     * @test
     */
    public function nestedStartReturnsExpectedLog(): void
    {
        $configuration1 = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration1);

        $log1 = $subject->getLog();
        self::assertInstanceOf(
            ImportLog::class,
            $log1
        );

        $configuration2 = new ImportConfiguration();
        $subject->start($configuration2);

        $log2 = $subject->getLog();
        self::assertInstanceOf(
            ImportLog::class,
            $log2
        );

        self::assertNotSame(
            $log1,
            $log2
        );
    }

    /**
     * @test
     */
    public function nestedImportMergesLog(): void
    {
        $configuration1 = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration1);

        $log1 = $subject->getLog();

        $configuration2 = new ImportConfiguration();
        $subject->start($configuration2);
        $importLogEntry = $this->createStub(ImportLogEntry::class);
        $subject->getLog()->addEntry($importLogEntry);
        $subject->end();

        self::assertSame(
            $log1,
            $subject->getLog()
        );

        self::assertSame(
            [
                $importLogEntry
            ],
            $log1->getEntries()->toArray()
        );
    }

    /**
     * @test
     */
    public function nestedImportReturnsHandledForRemoteId(): void
    {
        $configuration1 = new ImportConfiguration();
        $subject = new Import();
        $subject->start($configuration1);

        $configuration2 = new ImportConfiguration();
        $subject->start($configuration2);
        $importLogEntry = $this->createStub(ImportLogEntry::class);
        $importLogEntry->method('getRemoteId')->willReturn('https://example.com/remote-id');
        $subject->getLog()->addEntry($importLogEntry);
        $subject->end();

        $configuration3 = new ImportConfiguration();
        $subject->start($configuration3);
        self::assertTrue(
            $subject->handledRemoteId('https://example.com/remote-id')
        );
    }
}
