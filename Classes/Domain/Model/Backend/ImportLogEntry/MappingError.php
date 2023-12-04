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
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\MappingException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

class MappingError extends ImportLogEntry
{
    protected string $remoteId = '';

    protected string $errors = '';

    public function __construct(
        MappingException $exception
    ) {
        $this->remoteId = $exception->getUrl();
        $this->errors = json_encode([$exception->getMessage()]) ?: '';
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getErrors(): array
    {
        $errors = json_decode($this->errors, true);
        if (is_array($errors) === false) {
            throw new Exception('Could not parse errors.', 1671097690);
        }
        return $errors;
    }

    public function hasErrors(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'mappingError';
    }

    public function getInsertion(): array
    {
        return [];
    }
}
