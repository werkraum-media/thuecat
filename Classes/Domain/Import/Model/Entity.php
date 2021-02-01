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

interface Entity
{
    public function getTypo3StoragePid(): int;

    public function getTypo3DatabaseTableName(): string;

    /**
     * Return full remote id as delivered by remote API.
     */
    public function getRemoteId(): string;

    /**
     * Return db_column_name => db_value.
     */
    public function getData(): array;

    /**
     * Might be called during import.
     * Only in case entitiy already existed.
     * Is then called with existing UID from system.
     */
    public function setExistingTypo3Uid(int $uid): void;

    /**
     * Might be called during import.
     * Only in case entitiy didn't exist within system.
     * Is then called with new UID from system.
     */
    public function setImportedTypo3Uid(int $uid): void;

    /**
     * Should be 0 if no uid is known.
     */
    public function getTypo3Uid(): int;

    /**
     * Must return true in case the entitiy did not exist.
     */
    public function wasCreated(): bool;
}
