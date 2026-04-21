<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser;

use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\EntityInterface;

class DataHandlerPayload
{
    /** @var array<string, array<string, array>> */
    private array $data = [];

    public function addEntity(EntityInterface $entity): void
    {
        $table = $entity->table;
        $remoteId = $entity->toArray()['remote_id'];

        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }

        $this->data[$table][$remoteId] = $entity->toArray();
    }

    public function getPayload(): array
    {
        return $this->data;
    }


}
