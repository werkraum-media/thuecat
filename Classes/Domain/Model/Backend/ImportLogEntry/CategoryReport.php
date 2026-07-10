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
