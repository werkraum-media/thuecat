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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

// ISO-8601 strings, the shape DateEntity::configure() takes from either path.
//
// `canceled` only ever comes from a schedule: no cancellation key has been seen
// on an event node. It is carried anyway so both paths produce one shape, which
// is cheaper than a second type for one field.
final class EventOccurrence
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
        public readonly bool $canceled = false,
    ) {
    }
}
