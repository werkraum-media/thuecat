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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

// Event-level date keys that could not be resolved into an occurrence. Distinct
// from scheduleDaySkipped, whose payload is a day value of a schedule.
class EventDateSkipped extends AbstractError
{
    public function getType(): string
    {
        return 'eventDateSkipped';
    }
}
