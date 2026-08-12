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

namespace WerkraumMedia\ThueCat\Import\Http;

/** Per-run count of requests that failed and then succeeded. */
class RetryTally
{
    protected int $recoveredRequests = 0;

    protected int $wastedAttempts = 0;

    // Attempts beyond the first are the waste; one attempt is not a recovery.
    public function recordAttempts(int $attempts): void
    {
        if ($attempts < 2) {
            return;
        }

        $this->recoveredRequests++;
        $this->wastedAttempts += $attempts - 1;
    }

    public function recoveredRequests(): int
    {
        return $this->recoveredRequests;
    }

    public function wastedAttempts(): int
    {
        return $this->wastedAttempts;
    }

    public function hasRecoveries(): bool
    {
        return $this->recoveredRequests > 0;
    }

    public function reset(): void
    {
        $this->recoveredRequests = 0;
        $this->wastedAttempts = 0;
    }
}
