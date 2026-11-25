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

namespace WerkraumMedia\ThueCat\Import\Watchdog;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;

/**
 * Bounds one item's message, not any single operation: it is checked at the item's phase boundaries, so a
 * DataHandler pass already under way is never interrupted mid-write. Shared, because the fetches deep inside a
 * resolve check it too. Outside a started item it never expires, but it still records the phase reached, which a
 * failed item's log entry names.
 */
#[Autoconfigure(public: true)]
final class MessageDeadline
{
    private ?DateTimeImmutable $startedAt = null;

    private int $budgetSeconds = 0;

    private ?ImportPhase $phaseReached = null;

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    // The consumer is long-lived: a phase an earlier message reached must not be named for this one.
    public function reset(): void
    {
        $this->startedAt = null;
        $this->phaseReached = null;
    }

    public function start(int $budgetSeconds): void
    {
        $this->startedAt = $this->clock->now();
        $this->budgetSeconds = $budgetSeconds;
    }

    // Keeps the phase reached: the failure is logged after the item's message has ended.
    public function stop(): void
    {
        $this->startedAt = null;
    }

    public function phaseReached(): ?ImportPhase
    {
        return $this->phaseReached;
    }

    /**
     * @throws MessageBudgetExhaustedException
     */
    public function assertNotExpired(ImportPhase $phase): void
    {
        $this->phaseReached = $phase;
        if (!$this->startedAt instanceof DateTimeImmutable) {
            return;
        }

        $elapsed = $this->clock->now()->getTimestamp() - $this->startedAt->getTimestamp();
        if ($elapsed < $this->budgetSeconds) {
            return;
        }

        throw new MessageBudgetExhaustedException(
            sprintf('Message budget of %ds exhausted after %ds while %s.', $this->budgetSeconds, $elapsed, $phase->value),
            1795587176,
            $phase,
            $this->budgetSeconds,
            $elapsed
        );
    }
}
