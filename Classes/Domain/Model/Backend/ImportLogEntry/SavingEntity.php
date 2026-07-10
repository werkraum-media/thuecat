<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

class SavingEntity extends ImportLogEntry
{
    protected string $remoteId = '';

    protected int $insertion = 0;

    protected int $recordUid = 0;

    protected string $tableName = '';

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function wasInsertion(): bool
    {
        return $this->insertion === 1;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function getRecordDatabaseTableName(): string
    {
        return $this->tableName;
    }

    public function getType(): string
    {
        return 'savingEntity';
    }
}
