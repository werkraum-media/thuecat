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
 * A record met under another table than the one it is written to. remoteId
 * names the record, tableName and recordUid the row it was written to; the
 * table and uid it was known under are in the context.
 */
class RecordReclassification extends ImportLogEntry
{
    public const KIND_TABLE_MISMATCH = 'tableMismatch';
    public const KIND_REPAIRED = 'repaired';

    protected string $remoteId = '';

    protected string $kind = '';

    protected string $tableName = '';

    protected int $recordUid = 0;

    public function getType(): string
    {
        return 'recordReclassification';
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }
}
