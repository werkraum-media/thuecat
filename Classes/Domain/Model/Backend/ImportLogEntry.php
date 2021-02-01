<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as Typo3AbstractEntity;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;

class ImportLogEntry extends Typo3AbstractEntity
{
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

    protected array $errorsAsArray = [];

    public function __construct(
        Entity $entity,
        array $dataHandlerErrorLog
    ) {
        $this->insertion = $entity->wasCreated();
        $this->recordUid = $entity->getTypo3Uid();
        $this->recordPid = $entity->getTypo3StoragePid();
        $this->tableName = $entity->getTypo3DatabaseTableName();
        $this->errorsAsArray = $dataHandlerErrorLog;
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
            $this->errorsAsArray = json_decode($this->errors, true);
        }

        return $this->errorsAsArray;
    }

    public function hasErrors(): bool
    {
        return $this->getErrors() !== [];
    }
}
