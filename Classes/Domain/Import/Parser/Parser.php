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

use phpDocumentor\Reflection\Types\Boolean;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\AddressEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Exception\UnhandledNodeException;

class Parser
{
    protected DataHandlerPayload $dataHandlerPayload;


    private const TYPE_TO_ENTITY = [
        'schema:Organization' => OrganisationEntity::class,
        'thuecat:TouristAttraction' => TouristAttractionEntity::class,
        'schema:PostalAddress' => AddressEntity::class,
    ];

    private const NODE_SKIPPER = [
        'genid-',
    ];


    public function parse(array $graph): void
    {
        $this->dataHandlerPayload = new DataHandlerPayload();

        foreach ($graph as $node) {
            $id = $node['@id'] ?? '';
            $types = $node['@type'] ?? [];
            $types = is_array($types) ? $types : [];

            if ($this->determineNodeAction($id, $types) === false) {
                continue;
            }

            if ($this->determineNodeAction($id, $types) === true) {
                $entityClass = $this->resolveEntityClass($types);
                if ($entityClass === null) {
                    continue;
                }

                new $entityClass($node, $this->dataHandlerPayload);

            }
        }

    }

    public function getDataHandlerPayload(): DataHandlerPayload
    {
        return $this->dataHandlerPayload;
    }

    private function determineNodeAction(string $id, array $types): bool
    {
        foreach (self::NODE_SKIPPER as $pattern) {
            if (str_starts_with($pattern, $id)) {
                return false;
            }
        }

        foreach ($types as $type) {
            if (array_key_exists($type, self::TYPE_TO_ENTITY)) {
                return true;
            }
        }

        throw new UnhandledNodeException(
            'No handler defined for node ' . $id . ' with types: ' . implode(', ', $types)
        );
    }

    /** @return class-string<EntityInterface>|null */
    private function resolveEntityClass(array $types): ?string
    {
        foreach ($types as $type) {
            if (isset(self::TYPE_TO_ENTITY[$type])) {
                return self::TYPE_TO_ENTITY[$type];
            }
        }

        return null;
    }
}