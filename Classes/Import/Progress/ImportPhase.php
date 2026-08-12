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

namespace WerkraumMedia\ThueCat\Import\Progress;

// Cut along the importer's real structure, not an idealised pipeline.
enum ImportPhase: string
{
    case Fetch = 'fetch';
    case Resolve = 'resolve';
    case Persist = 'persist';
    case Log = 'log';
    case Finish = 'finish';
}
