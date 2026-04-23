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

#[Autoconfigure(public: true)]
class Parser
{
    private DataHandlerPayload $dataHandlerPayload;

    private string $language = 'de';

    public function __construct(
        // this finds and instantiates all Classes implementing the EntityInterface (which contains the service tag)
        #[AutowireLocator(services: 'import.entity')]
        private readonly ServiceLocator $entities,
    ) {
    }

    /**
     * @param string $language Site-language tag used to pick the default row for
     *                         multi-locale JSON-LD fields (defaults to German).
     *                         The ImporterCommand will derive this from the
     *                         target folder's site configuration.
     */
    public function parse(array $graph, string $language = 'de'): void
    {
        // Fresh payload per parse() call so repeated imports don't accumulate state.
        $this->dataHandlerPayload = new DataHandlerPayload();
        $this->language = $language;

        foreach ($graph as $node) {
            if (!is_array($node)) {
                continue;
            }
            $this->parseNode($node);
        }
    }

    public function getDataHandlerPayload(): DataHandlerPayload
    {
        return $this->dataHandlerPayload;
    }

    public function parseNode(array $node): string
    {
        $entity = $this->resolveEntity($node['@type'] ?? []);
        if ($entity === null) {
            return '';
        }

        $entity->configure($node, $this->language);
        $this->dataHandlerPayload->addEntity($entity);

        return 'REF:' . $entity->getRemoteId($node);
    }

    private function resolveEntity(mixed $types): ?EntityInterface
    {
        $types = is_array($types) ? $types : [];
        if ($types === []) {
            return null;
        }

        // A JSON-LD node usually carries multiple @types (e.g. a TouristAttraction
        // is also Place, Thing, CivicStructure…). Collect every entity that claims
        // any of them, then let priority break ties — more specific entities
        // (TouristInformation, priority 20) win over generic ones.
        $candidates = [];
        foreach ($this->entities as $candidate) {
            foreach ($types as $type) {
                if (in_array($type, $candidate->handlesTypes(), true)) {
                    $candidates[] = $candidate;
                    break;
                }
            }
        }
        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (EntityInterface $a, EntityInterface $b) => $b->getPriority() <=> $a->getPriority()
        );

        return $candidates[0];
    }
}
