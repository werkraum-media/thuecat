<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Watchdog;

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
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Watchdog\RunBudgetExhaustedException;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;

class RunDeadlineTest extends TestCase
{
    #[Test]
    public function isNotExpiredWithinItsBudget(): void
    {
        $subject = new RunDeadline(60, microtime(true));

        self::assertFalse($subject->isExpired());
    }

    #[Test]
    public function isExpiredOnceTheBudgetHasPassed(): void
    {
        $subject = new RunDeadline(10, microtime(true) - 11.0);

        self::assertTrue($subject->isExpired());
    }

    #[Test]
    public function assertingPassesQuietlyWithinBudget(): void
    {
        $subject = new RunDeadline(60, microtime(true));

        $subject->assertNotExpired(ImportPhase::Fetch);

        self::assertFalse($subject->isExpired());
    }

    #[Test]
    public function assertingRaisesOnceExhausted(): void
    {
        $subject = new RunDeadline(10, microtime(true) - 12.5);

        try {
            $subject->assertNotExpired(ImportPhase::Persist);
            self::fail('Expected RunBudgetExhaustedException.');
        } catch (RunBudgetExhaustedException $exception) {
            self::assertSame(ImportPhase::Persist, $exception->phase, 'The phase reached is carried.');
            self::assertSame(10, $exception->budgetSeconds);
            self::assertGreaterThanOrEqual(12.5, $exception->elapsedSeconds);
        }
    }

    // 0 means "not set" for every tunable, so it must not abort instantly.
    #[Test]
    public function zeroBudgetNeverExpires(): void
    {
        $subject = new RunDeadline(0, microtime(true) - 100000.0);

        self::assertFalse($subject->isExpired());
        $subject->assertNotExpired(ImportPhase::Fetch);
    }
}
