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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

/**
 * Base for match-report entries. remoteId holds the raw source value, kind the
 * field, recordUid the resolved category for matched entries.
 */
abstract class CategoryReport extends ImportLogEntry
{
    protected string $remoteId = '';

    protected string $kind = '';

    protected int $recordUid = 0;

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }
}
