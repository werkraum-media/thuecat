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

// Base for error entries. message and severity (on ImportLogEntry) carry what
// the report renders; concretes only name their record type.
abstract class AbstractError extends ImportLogEntry
{
    protected string $remoteId = '';

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }
}
