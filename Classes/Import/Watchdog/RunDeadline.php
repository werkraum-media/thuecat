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

namespace WerkraumMedia\ThueCat\Import\Watchdog;

use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;

/**
 * Bounds the run, not any single operation: checked at phase boundaries, so a
 * DataHandler pass already under way is never interrupted mid-write.
 */
class RunDeadline
{
    protected readonly float $startedAt;

    public function __construct(
        protected readonly int $budgetSeconds,
        ?float $startedAt = null
    ) {
        $this->startedAt = $startedAt ?? microtime(true);
    }

    public function isExpired(): bool
    {
        return $this->budgetSeconds > 0 && $this->elapsed() >= $this->budgetSeconds;
    }

    public function elapsed(): float
    {
        return microtime(true) - $this->startedAt;
    }

    /**
     * @throws RunBudgetExhaustedException
     */
    public function assertNotExpired(ImportPhase $phase): void
    {
        if (!$this->isExpired()) {
            return;
        }

        throw new RunBudgetExhaustedException(
            sprintf(
                'Run budget of %ds exhausted after %.1fs while %s.',
                $this->budgetSeconds,
                $this->elapsed(),
                $phase->value
            ),
            1786531206,
            $phase,
            $this->budgetSeconds,
            $this->elapsed()
        );
    }
}
