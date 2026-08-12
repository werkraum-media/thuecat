<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Command;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use WerkraumMedia\ThueCat\Command\ConsoleProgressRenderer;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;

class ConsoleProgressRendererTest extends TestCase
{
    #[Test]
    public function announcesEachPhaseOnFirstEventOnly(): void
    {
        $output = new BufferedOutput();
        $subject = new ConsoleProgressRenderer($output, true);

        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'a', 1, 3));
        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-1'));
        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'b', 2, 3));
        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-2'));
        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'c', 3, 3));

        $display = $output->fetch();

        self::assertSame(1, substr_count($display, 'Fetching configured URLs'));
        self::assertSame(1, substr_count($display, 'Resolving references and media'));
    }

    #[Test]
    public function debugVerbosityNamesEachItem(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $subject = new ConsoleProgressRenderer($output, true);

        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'first', 1, 2));
        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'second', 2, 2));

        $display = $output->fetch();

        self::assertStringContainsString('fetching 2/2 second', $display);
        self::assertStringContainsString('first', $display, 'The announcing item is named too.');
    }

    /**
     * The gap between items is what identifies a slow request.
     */
    #[Test]
    public function debugVerbosityTimesEachItem(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $subject = new ConsoleProgressRenderer($output, true);

        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-1'));
        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-2'));

        self::assertMatchesRegularExpression(
            '/\[\s*\d+\.\d{2}s\] resolving ref-2/',
            $output->fetch()
        );
    }

    /**
     * A tally of internal requests says nothing about remaining work, so an
     * uncountable phase is announced and then stays quiet.
     */
    #[Test]
    public function uncountablePhasesReportNoCounterAtDefaultVerbosity(): void
    {
        $output = new BufferedOutput();
        $subject = new ConsoleProgressRenderer($output, true);

        for ($i = 0; $i < 50; $i++) {
            $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-' . $i));
        }

        $display = $output->fetch();

        self::assertStringContainsString('Resolving references and media', $display);
        self::assertStringNotContainsString('requests', $display);
        self::assertCount(
            1,
            array_filter(explode(PHP_EOL, $display)),
            'The headline is the only line an uncountable phase produces.'
        );
    }

    #[Test]
    public function defaultVerbosityOmitsItemLabels(): void
    {
        $output = new BufferedOutput();
        $subject = new ConsoleProgressRenderer($output, true);

        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'first', 1, 2));
        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'second', 2, 2));

        $display = $output->fetch();

        self::assertStringNotContainsString('second', $display, 'Item labels are debug-only.');
        self::assertStringContainsString('fetching 2/2', $display, 'The position still shows.');
    }

    #[Test]
    public function nonInteractiveOutputPrintsHeadlinesOnly(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $subject = new ConsoleProgressRenderer($output, false);

        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'first', 1, 2));
        $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'second', 2, 2));

        $display = $output->fetch();

        self::assertStringContainsString('Fetching configured URLs', $display);
        self::assertStringNotContainsString('second', $display);
        self::assertStringNotContainsString("\r", $display);
    }

    // Without the throttle a long root list prints one line per root.
    #[Test]
    public function countableProgressIsThrottled(): void
    {
        $output = new BufferedOutput();
        $subject = new ConsoleProgressRenderer($output, true);

        for ($i = 1; $i <= 500; $i++) {
            $subject->progressed(new ImportProgress(ImportPhase::Fetch, 'url-' . $i, $i, 500));
        }

        $lines = array_filter(explode(PHP_EOL, $output->fetch()));

        self::assertLessThan(
            10,
            count($lines),
            'A tight loop yields the headline plus at most a couple of throttled lines.'
        );
    }

    #[Test]
    public function everyDetailLineNamesItsPhase(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $subject = new ConsoleProgressRenderer($output, true);

        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-1'));
        $subject->progressed(new ImportProgress(ImportPhase::Resolve, 'ref-2'));

        self::assertStringContainsString('resolving ref-2', $output->fetch());
    }
}
