<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Domain\Import\EntityMapper;

/**
 * Registry with supported entities and their types.
 */
class EntityRegistry
{
    /**
     * @var array[]
     */
    private array $entities = [];

    /**
     * @param string[] $supportedTypes
     */
    public function registerEntityClass(
        string $entityClassName,
        int $priority,
        array $supportedTypes
    ): void {
        foreach ($supportedTypes as $supportedType) {
            $this->entities[$supportedType][] = [
                'priority' => $priority,
                'entityClassName' => $entityClassName,
            ];
        }
    }

    public function getEntityByTypes(array $types): string
    {
        $matches = [];

        foreach ($types as $type) {
            if (isset($this->entities[$type]) === false) {
                continue;
            }

            foreach ($this->entities[$type] as $entityConfiguration) {
                $matches[] = $entityConfiguration;
            }
        }

        if ($matches === []) {
            return '';
        }

        usort($matches, static function (array $configA, array $configB): int {
            return $configA['priority'] <=> $configB['priority'];
        });

        return end($matches)['entityClassName'];
    }
}
