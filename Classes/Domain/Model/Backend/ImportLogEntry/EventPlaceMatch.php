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
 * One attempt to relate an event to a place. remoteId names the event,
 * tableName and recordUid the place where one was found, kind the outcome.
 */
class EventPlaceMatch extends ImportLogEntry
{
    public const OUTCOME_BY_REFERENCE = 'resolvedByReference';
    public const OUTCOME_BY_NAME_AND_POSTAL_CODE = 'resolvedByNameAndPostalCode';
    public const OUTCOME_AMBIGUOUS = 'ambiguous';
    public const OUTCOME_UNRESOLVED_REFERENCE = 'unresolvedReference';
    public const OUTCOME_UNMATCHED = 'unmatched';

    protected string $remoteId = '';

    protected string $kind = '';

    protected string $tableName = '';

    protected int $recordUid = 0;

    public function getType(): string
    {
        return 'eventPlaceMatch';
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

    public function isResolved(): bool
    {
        return $this->recordUid > 0;
    }
}
