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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use WerkraumMedia\ThueCat\Command\ImportConfigurationCommand;

final class ImportConfigurationCommandTest extends AbstractImportTestCase
{
    #[Test]
    public function canImport(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    #[Test]
    public function reportsSuccessOnCleanRun(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $exitCode = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('completed', $tester->getDisplay());
    }

    /** A recovered retry raises severity to `notice`; that must not reach the exit code. */
    #[Test]
    public function reportsRecoveredRetryRunAsSuccess(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFailure('018132452787-ngbe', 503, 'Service Unavailable');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $exitCode = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('completed', $tester->getDisplay());
    }

    #[Test]
    public function reportsWarningRunAsSuccess(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $tester = new CommandTester($subject);
        $exitCode = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        $display = $tester->getDisplay();

        self::assertSame(
            Command::SUCCESS,
            $exitCode,
            'Skipped references must not fail the run.'
        );
        self::assertStringContainsString(
            'completed with warnings',
            $display,
            'Severity carries no cause, so the message must not claim the warning '
            . 'came from a skipped reference — it holds for any warning source.'
        );
        self::assertStringContainsString('import log', $display);
    }

    #[Test]
    public function reportsFailureOnFailedRun(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectNotFound('043064193523-jcyt');

        $tester = new CommandTester($subject);
        $exitCode = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('failed', $tester->getDisplay());
    }

    #[Test]
    public function reportsPhaseTransitionsWhileImporting(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        $display = $tester->getDisplay();

        self::assertStringContainsString('Fetching', $display, 'The run names the phase it is in.');
        self::assertStringContainsString('Persisting', $display);
        self::assertStringContainsString('completed', $display, 'The closing line still prints.');
    }

    /**
     * CommandTester has no TTY, which is what a cron or CI run looks like.
     * Per-item churn there would flood the log.
     */
    #[Test]
    public function nonInteractiveOutputOmitsPerItemProgress(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        $display = $tester->getDisplay();

        self::assertStringNotContainsString(
            'https://thuecat.org/resources/018132452787-ngbe',
            $display,
            'Individual fetched resources are not listed when output is not a terminal.'
        );
        self::assertStringNotContainsString("\r", $display, 'No ANSI redraw without a terminal.');
        self::assertStringContainsString('Fetching', $display, 'Phase transitions still print.');
    }

    /**
     * Smoke-level only: this fixture emits one event per phase, so re-entry is
     * covered by ConsoleProgressRendererTest instead.
     */
    #[Test]
    public function eachPhaseIsAnnouncedOnlyOnce(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $tester = new CommandTester($subject);
        $tester->execute(
            ['configuration' => 1],
            ['capture_stderr_separately' => true, 'decorated' => true]
        );

        $display = $tester->getDisplay();

        self::assertSame(
            1,
            substr_count($display, 'Fetching configured URLs'),
            'The fetch phase is announced once, not once per URL.'
        );
        self::assertSame(
            1,
            substr_count($display, 'Resolving references and media'),
            'The resolve phase is announced once, however often it is re-entered.'
        );
    }

    #[Test]
    public function progressRenderingLeavesExitCodesUnchanged(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectNotFound('043064193523-jcyt');

        $tester = new CommandTester($subject);
        $exitCode = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::FAILURE, $exitCode, 'A failed run still exits non-zero.');
    }

    #[Test]
    public function throwsExceptionOnNoneExistingConfiguration(): void
    {
        $this->workaroundExtbaseConfiguration();

        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $tester = new CommandTester($subject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No configuration found for uid: 1');
        $this->expectExceptionCode(1693228522);

        $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);
    }

    #[Test]
    public function throwsExceptionOnMissingArgument(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $tester = new CommandTester($subject);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "configuration")');
        $this->expectExceptionCode(0);

        $tester->execute([], ['capture_stderr_separately' => true]);
    }

    #[Test]
    public function throwsExceptionOnNoneNumericConfigurationArgument(): void
    {
        $subject = $this->getContainer()->get(ImportConfigurationCommand::class);

        $tester = new CommandTester($subject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No numeric uid for configuration provided.');
        $this->expectExceptionCode(1643267138);

        $tester->execute(['configuration' => 'a'], ['capture_stderr_separately' => true]);
    }
}
