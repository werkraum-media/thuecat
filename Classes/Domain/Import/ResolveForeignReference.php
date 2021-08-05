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

namespace WerkraumMedia\ThueCat\Domain\Import;

use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\EntityRegistry;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;

class ResolveForeignReference
{
    /**
     * @var FetchData
     */
    private $fetchData;

    /**
     * @var EntityRegistry
     */
    private $entityRegistry;

    /**
     * @var EntityMapper
     */
    private $entityMapper;

    public function __construct(
        FetchData $fetchData,
        EntityRegistry $entityRegistry,
        EntityMapper $entityMapper
    ) {
        $this->fetchData = $fetchData;
        $this->entityRegistry = $entityRegistry;
        $this->entityMapper = $entityMapper;
    }

    public function resolve(
        ForeignReference $foreignReference,
        string $language
    ): ?object {
        $jsonLD = $this->fetchData->jsonLDFromUrl($foreignReference->getId());
        $jsonEntity = $jsonLD['@graph'][0] ?? null;
        if ($jsonEntity === null) {
            return null;
        }

        $targetEntity = $this->entityRegistry->getEntityByTypes($jsonEntity['@type']);
        if ($targetEntity === '') {
            return null;
        }

        return $this->entityMapper->mapDataToEntity(
            $jsonEntity,
            $targetEntity,
            [
                JsonDecode::ACTIVE_LANGUAGE => $language,
            ]
        );
    }
}
