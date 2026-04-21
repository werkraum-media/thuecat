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
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Exception\UnhandledNodeException;

class Parser
{
    private const TYPE_TO_ENTITY = [
        'schema:Organization' => OrganisationEntity::class,
    ];

    private const NODE_HANDLER = [
        'schema:Organization' => 'process',
    ];

    private const NODE_SKIPPER = [
        'genid-' => 'discard',
    ];

    /** @var array<int, array{id: string, type: string, action: string}> */
    private array $nodeLog = [];

    public function parse(array $graph): array
    {
        $result = [];
        $this->nodeLog = [];

        foreach ($graph as $node) {
            $id = $node['@id'] ?? '';
            $types = $node['@type'] ?? [];
            $types = is_array($types) ? $types : [];

            $action = $this->determineNodeAction($id, $types);

            $this->logNode($id, $types, $action);

            if ($action === 'discard') {
                continue;
            }

            if ($action === 'process') {
                $entityClass = $this->resolveEntityClass($types);
                if ($entityClass === null) {
                    continue;
                }

                $entity = new $entityClass($node);
                $result[$entity->getTable()][$entity->getRemoteId()] = $entity->toDataHandlerArray();
            }
        }

        return $result;
    }

    public function getNodeLog(): array
    {
        return $this->nodeLog;
    }

    private function determineNodeAction(string $id, array $types): string
    {
        foreach (self::NODE_SKIPPER as $pattern => $action) {
            if (str_contains($id, $pattern)) {
                return $action;
            }
        }

        foreach ($types as $type) {
            if (isset(self::NODE_HANDLER[$type])) {
                return self::NODE_HANDLER[$type];
            }
        }

        throw new UnhandledNodeException(
            'No handler defined for node ' . $id . ' with types: ' . implode(', ', $types)
        );
    }

    private function logNode(string $id, array $types, string $action): void
    {
        $this->nodeLog[] = [
            'id' => $id,
            'type' => !empty($types) ? implode(', ', $types) : '(no type)',
            'action' => $action,
        ];
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