<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use WerkraumMedia\ThueCat\Command\ImportConfigurationCommand;

/**
 * @covers \WerkraumMedia\ThueCat\Command\ImportConfigurationCommand
 * @testdox The 'thuecat:importviaconfiguration' command
 */
final class ImportConfigurationCommandTest extends AbstractImportTest
{
    /**
     * @test
     */
    public function canImport(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);
        self::assertInstanceOf(Command::class, $subject);

        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.xml');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsFreshOrganization.csv');
    }

    /**
     * @test
     */
    public function throwsExceptionOnNoneExistingConfiguration(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);
        self::assertInstanceOf(Command::class, $subject);

        $tester = new CommandTester($subject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No configuration found for uid: 1');
        $this->expectExceptionCode(1693228522);

        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);
    }

    /**
     * @test
     */
    public function throwsExceptionOnMissingArgument(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);
        self::assertInstanceOf(Command::class, $subject);

        $tester = new CommandTester($subject);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "configuration")');
        $this->expectExceptionCode(0);

        $tester->execute([], ['capture_stderr_separately' => true]);
    }

    /**
     * @test
     */
    public function throwsExceptionOnNoneNumericConfigurationArgument(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);
        self::assertInstanceOf(Command::class, $subject);

        $tester = new CommandTester($subject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No numeric uid for configuration provided.');
        $this->expectExceptionCode(1643267138);

        $tester->execute(['configuration' => 'a'], ['capture_stderr_separately' => true]);
    }
}
