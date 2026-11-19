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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use RuntimeException;
use WerkraumMedia\ThueCat\Removal\RemovalJob;
use WerkraumMedia\ThueCat\Removal\RootOutcome;
use WerkraumMedia\ThueCat\Removal\RootRecord;
use WerkraumMedia\ThueCat\Removal\RootRemoval;

/**
 * Fails the way a lost database connection or a killed worker fails a root.
 */
final class RootRemovalThrowingStub extends RootRemoval
{
    public function __construct()
    {
    }

    public function remove(RemovalJob $job, RootRecord $root): RootOutcome
    {
        throw new RuntimeException('Removal of ' . $root->table . ':' . $root->uid . ' failed [test]', 1795085211);
    }
}
