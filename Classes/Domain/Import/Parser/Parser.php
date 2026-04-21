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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristInformationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Exception\UnhandledNodeException;

#[Autoconfigure(public: true)]
class Parser
{
    protected DataHandlerPayload $dataHandlerPayload;


    private const TYPE_TO_ENTITY = [
        'thuecat:TouristInformation' => TouristInformationEntity::class,
        'schema:Organization' => OrganisationEntity::class,
        'thuecat:TouristAttraction' => TouristAttractionEntity::class,

    ];

    private const NODE_SKIPPER = [
        'genid-',
    ];

    public function __construct(#[AutowireLocator(services: 'import.entity')] private readonly ServiceLocator $entities)
    {

    }


    public function parse(array $graph): void
    {
        $this->dataHandlerPayload = new DataHandlerPayload();

        foreach ($graph as $node) {
            $types = $node['@type'] ?? [];
            $types = is_array($types) ? $types : [];

            $entityClass = $this->resolveEntityClass($types);
            if ($entityClass === null) {
                continue;
            }
            $entity = new $entityClass();
            $entity->configure($node);
            $this->dataHandlerPayload->addEntity($entity);

        }

    }

    public function getDataHandlerPayload(): DataHandlerPayload
    {
        return $this->dataHandlerPayload;
    }

    private function resolveEntityClass(array $types): EntityInterface
    {
        $candidates = [];
        foreach ($this->entities as $candidate) {
            foreach ($types as $type) {
                if (in_array($type, $candidate->handlesTypes())) {
                    $candidates[] = $candidate;
                }
            }
        }
        if (count($candidates) > 1) {
            usort($candidates, function (EntityInterface $a, EntityInterface $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return 0;
                }
                return $a->getPriority() < $b->getPriority() ? 1 : -1;
            });
        }
        return array_shift($candidates);
    }
}