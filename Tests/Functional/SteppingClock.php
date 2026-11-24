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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Answers the given instants in order and keeps answering the last one, so a duration measured between two readings
 * is known in advance.
 */
final class SteppingClock implements ClockInterface
{
    /**
     * @var list<DateTimeImmutable>
     */
    private array $instants;

    public function __construct(DateTimeImmutable ...$instants)
    {
        $this->instants = array_values($instants);
    }

    public function now(): DateTimeImmutable
    {
        return count($this->instants) > 1 ? array_shift($this->instants) : $this->instants[0];
    }
}
