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

use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelReaper;

/**
 * Answers every deletion the way DataHandler refuses one, without deleting.
 */
final class SyncChannelReaperFailingStub extends SyncChannelReaper
{
    public const ERROR = 'Attempt to delete record without delete-permissions [test]';

    public function __construct()
    {
    }

    public function delete(array $remoteIds, array $sitePageIds): array
    {
        return [self::ERROR];
    }
}
