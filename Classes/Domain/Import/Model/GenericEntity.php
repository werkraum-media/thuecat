<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

class GenericEntity implements Entity
{
    private int $typo3StoragePid;
    private string $typo3DatabaseTableName;
    private bool $created = false;
    private int $typo3Uid = 0;
    private string $remoteId;
    private array $data;

    public function __construct(
        int $typo3StoragePid,
        string $typo3DatabaseTableName,
        string $remoteId,
        array $data
    ) {
        $this->typo3StoragePid = $typo3StoragePid;
        $this->typo3DatabaseTableName = $typo3DatabaseTableName;
        $this->remoteId = $remoteId;
        $this->data = $data;
    }

    public function getTypo3StoragePid(): int
    {
        return $this->typo3StoragePid;
    }

    public function getTypo3DatabaseTableName(): string
    {
        return $this->typo3DatabaseTableName;
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setImportedTypo3Uid(int $uid): void
    {
        $this->typo3Uid = $uid;
        $this->created = true;
    }

    public function setExistingTypo3Uid(int $uid): void
    {
        $this->typo3Uid = $uid;
        $this->created = false;
    }

    public function getTypo3Uid(): int
    {
        return $this->typo3Uid;
    }

    public function wasCreated(): bool
    {
        return $this->created;
    }
}
