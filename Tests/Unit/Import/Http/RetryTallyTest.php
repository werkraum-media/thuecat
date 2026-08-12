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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Http\RetryTally;

/**
 * Counts requests that failed and then succeeded — the signal that separates a
 * struggling upstream from a healthy one, which exhaustion alone never shows.
 */
class RetryTallyTest extends TestCase
{
    #[Test]
    public function startsEmpty(): void
    {
        $subject = new RetryTally();

        self::assertSame(0, $subject->recoveredRequests());
        self::assertSame(0, $subject->wastedAttempts());
        self::assertFalse($subject->hasRecoveries());
    }

    #[Test]
    public function aFirstAttemptSuccessIsNotARecovery(): void
    {
        $subject = new RetryTally();

        $subject->recordAttempts(1);

        self::assertSame(0, $subject->recoveredRequests(), 'One attempt means nothing went wrong.');
        self::assertFalse($subject->hasRecoveries());
    }

    #[Test]
    public function aRecoveredRequestCountsItsWastedAttempts(): void
    {
        $subject = new RetryTally();

        $subject->recordAttempts(3);

        self::assertSame(1, $subject->recoveredRequests());
        // Three attempts to fetch one resource wasted two of them.
        self::assertSame(2, $subject->wastedAttempts());
        self::assertTrue($subject->hasRecoveries());
    }

    #[Test]
    public function recoveriesAccumulateAcrossRequests(): void
    {
        $subject = new RetryTally();

        $subject->recordAttempts(2);
        $subject->recordAttempts(1);
        $subject->recordAttempts(3);

        self::assertSame(2, $subject->recoveredRequests(), 'The single-attempt request does not count.');
        self::assertSame(3, $subject->wastedAttempts());
    }

    #[Test]
    public function resetClearsTheTallyForTheNextRun(): void
    {
        $subject = new RetryTally();
        $subject->recordAttempts(2);

        $subject->reset();

        self::assertSame(0, $subject->recoveredRequests());
        self::assertFalse($subject->hasRecoveries());
    }
}
