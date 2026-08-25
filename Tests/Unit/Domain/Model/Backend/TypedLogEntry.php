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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Backend;

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

/**
 * Stands in for any entry type, so grouping can be tested without binding the
 * assertions to one concrete subclass.
 */
class TypedLogEntry extends ImportLogEntry
{
    protected string $remoteId = '';

    public function __construct(
        private readonly string $entryType
    ) {
    }

    public function getType(): string
    {
        return $this->entryType;
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }
}
