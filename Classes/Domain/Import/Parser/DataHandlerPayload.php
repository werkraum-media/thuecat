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
    /** @var array<string, array<int|string, array<string, string|int|float>>> */
    private array $data = [];

    /**
     * Unresolved references, bound to (table, remote_id) so the resolver can
     * write each resolved uid back into the correct row without risk of mixing
     * up records that share similar raw refs.
     *
     * @var array<string, array<string, array<string, list<string>>>>
     */
    private array $transients = [];

    public function addEntity(EntityInterface $entity): void
    {
        /** @var string $table */
        $table = $entity->table;
        $row = $entity->toArray();
        $remoteId = (string)$row['remote_id'];

        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }

        $this->data[$table][$remoteId] = $row;

        $entityTransients = $entity->getTransients();
        if ($entityTransients !== []) {
            $this->transients[$table][$remoteId] = $entityTransients;
        }
    }

    /**
     * Swap the outer key of an already-registered row. Used by the resolver
     * to replace the remote-id URL with either an existing uid or a
     * StringUtility::getUniqueId('NEW') placeholder, turning the payload
     * into a valid DataHandler datamap in place.
     */
    public function rekeyRow(string $table, string $oldKey, string $newKey): void
    {
        if (!isset($this->data[$table][$oldKey])) {
            return;
        }

        $this->data[$table][$newKey] = $this->data[$table][$oldKey];
        unset($this->data[$table][$oldKey]);
    }

    /**
     * Write a single field onto a row already present in the payload.
     * Used by the resolver to inject `pid` and, later, to fill resolved
     * uid references into relation fields.
     */
    public function setField(string $table, string $key, string $field, string|int|float $value): void
    {
        if (!isset($this->data[$table][$key])) {
            return;
        }

        $this->data[$table][$key][$field] = $value;
    }

    /**
     * @return array<string, array<int|string, array<string, string|int|float>>>
     */
    public function getPayload(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, array<string, array<string, list<string>>>>
     */
    public function getTransients(): array
    {
        return $this->transients;
    }
}
