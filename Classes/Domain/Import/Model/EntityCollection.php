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

class EntityCollection
{
    /**
     * @var Entity[]
     */
    private array $entities = [];

    public function add(Entity $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getDefaultLanguageEntity(): ?Entity
    {
        foreach ($this->entities as $entity) {
            if ($entity->isForDefaultLanguage()) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @return Entity[]
     */
    public function getEntitiesToTranslate(): array
    {
        return array_filter($this->entities, function (Entity $entity) {
            return $entity->isTranslation()
                && $entity->exists() === false
                ;
        });
    }

    public function getExistingEntities(): array
    {
        return array_filter($this->entities, function (Entity $entity) {
            return $entity->exists();
        });
    }
}
