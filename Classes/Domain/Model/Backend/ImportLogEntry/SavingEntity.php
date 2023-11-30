<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

use Exception;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

class SavingEntity extends ImportLogEntry
{
    /**
     * @var string
     */
    protected $remoteId = '';

    /**
     * @var bool
     */
    protected $insertion = false;

    /**
     * @var int
     */
    protected $recordUid = 0;

    /**
     * @var int
     */
    protected $recordPid = 0;

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var string
     */
    protected $errors = '';

    /**
     * @var string[]
     */
    protected $errorsAsArray = [];

    public function __construct(
        Entity $entity,
        array $dataHandlerErrorLog
    ) {
        $this->remoteId = $entity->getRemoteId();
        $this->insertion = $entity->wasCreated();
        $this->recordUid = $entity->getTypo3Uid();
        $this->recordPid = $entity->getTypo3StoragePid();
        $this->tableName = $entity->getTypo3DatabaseTableName();
        $this->errorsAsArray = $dataHandlerErrorLog;
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function wasInsertion(): bool
    {
        return $this->insertion;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function getRecordDatabaseTableName(): string
    {
        return $this->tableName;
    }

    public function getErrors(): array
    {
        if ($this->errorsAsArray === [] && $this->errors !== '') {
            $errorsAsArray = json_decode($this->errors, true);
            if (is_array($errorsAsArray) === false) {
                throw new Exception('Could not parse errors.', 1671097690);
            }
            $this->errorsAsArray = array_unique($errorsAsArray);
        }

        return $this->errorsAsArray;
    }

    public function hasErrors(): bool
    {
        return $this->getErrors() !== [];
    }

    public function getType(): string
    {
        return 'savingEntity';
    }

    public function getInsertion(): array
    {
        return [
            'insertion' => (int)$this->wasInsertion(),
            'record_uid' => $this->getRecordUid(),
            'table_name' => $this->getRecordDatabaseTableName(),
        ];
    }
}
