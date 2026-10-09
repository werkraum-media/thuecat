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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

use WerkraumMedia\Events\Domain\Model\Import;

// Minimal Import stub used solely to satisfy DatesFactory::createDates(),
// which only ever calls $import->getRepeatUntil() (a string fed to
// DateTimeImmutable::modify, e.g. '+60 days'). Bypasses the real Import
// constructor's 13-arg signature — none of those fields are read by
// DatesFactory's recurring-expansion code path.
//
// If DatesFactory ever starts reading other Import fields, either populate
// them here or switch to a properly constructed Import. For now this is the
// smallest surface that makes the factory work for our import.
final class StubImport extends Import
{
    public function __construct(string $repeatUntil = '+60 days')
    {
        // Parent constructor deliberately not called — see class docblock.
        $this->importRepeatUntil = $repeatUntil;
    }
}
