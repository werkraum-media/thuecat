<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Command;

use Symfony\Component\Console\Output\OutputInterface;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;

// Lives with the command, not the importer, which stays console-free.
final class ConsoleProgressRenderer implements ImportProgressListener
{
    /**
     * @var array<string, bool>
     */
    private array $announced = [];

    private float $lastDetailAt = 0.0;

    private float $lastItemAt = 0.0;

    // Slow enough that a fast fan-out cannot flood a terminal.
    private const DETAIL_INTERVAL_SECONDS = 0.2;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly bool $interactive
    ) {
    }

    public function progressed(ImportProgress $progress): void
    {
        // Fetch and resolve interleave per URL, so a phase is announced on
        // first entry only; re-entry is ordinary throttled detail.
        if (!isset($this->announced[$progress->phase->value])) {
            $this->announced[$progress->phase->value] = true;
            $this->output->writeln($this->phaseHeadline($progress));

            // Debug wants every item, including the one that announced.
            if ($this->interactive && $this->output->isDebug()) {
                $this->writeDetail($progress);
            }
            return;
        }

        if (!$this->interactive) {
            return;
        }

        $this->writeDetail($progress);
    }

    private function phaseHeadline(ImportProgress $progress): string
    {
        $headline = match ($progress->phase) {
            ImportPhase::Fetch => 'Fetching configured URLs',
            ImportPhase::Resolve => 'Resolving references and media',
            ImportPhase::Persist => 'Persisting records',
            ImportPhase::Log => 'Writing import log',
            ImportPhase::Finish => 'Finishing',
        };

        if ($progress->hasPosition()) {
            $headline .= sprintf(' (%d/%d)', $progress->current, $progress->total);
        }

        return '<info>' . $headline . '</info>';
    }

    /**
     * Per-item lines are debug detail (-vvv). Below that only a phase with a
     * real position says anything: progress means "how much is left", and a
     * tally of internal requests cannot answer that.
     */
    private function writeDetail(ImportProgress $progress): void
    {
        if ($this->output->isDebug()) {
            $this->output->writeln('  ' . $this->itemLabel($progress));
            return;
        }

        if (!$progress->hasPosition()) {
            return;
        }

        $now = microtime(true);
        if (($now - $this->lastDetailAt) < self::DETAIL_INTERVAL_SECONDS) {
            return;
        }
        $this->lastDetailAt = $now;

        $this->output->writeln('  ' . $this->counterLabel($progress));
    }

    /**
     * Carries the elapsed time since the previous item so a slow request is
     * visible as a gap rather than having to be timed by hand.
     */
    private function itemLabel(ImportProgress $progress): string
    {
        $now = microtime(true);
        $sincePrevious = $this->lastItemAt > 0.0 ? $now - $this->lastItemAt : 0.0;
        $this->lastItemAt = $now;

        $position = $progress->hasPosition()
            ? sprintf(' %d/%d', $progress->current, $progress->total)
            : '';

        return sprintf(
            '[%5.2fs] %s%s %s',
            $sincePrevious,
            $this->phaseNoun($progress->phase),
            $position,
            $progress->label
        );
    }

    private function counterLabel(ImportProgress $progress): string
    {
        return sprintf('%s %d/%d', $this->phaseNoun($progress->phase), $progress->current, $progress->total);
    }

    // Re-entry lines must say which phase they belong to; the headline is
    // long gone by then.
    private function phaseNoun(ImportPhase $phase): string
    {
        return match ($phase) {
            ImportPhase::Fetch => 'fetching',
            ImportPhase::Resolve => 'resolving',
            ImportPhase::Persist => 'persisting',
            ImportPhase::Log => 'logging',
            ImportPhase::Finish => 'finishing',
        };
    }
}
