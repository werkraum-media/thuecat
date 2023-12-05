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

namespace WerkraumMedia\ThueCat\Domain\Repository\Backend;

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Town;

class TownRepository extends Repository
{
    public function __construct(
        Typo3QuerySettings $querySettings
    ) {
        parent::__construct();

        $querySettings->setRespectStoragePage(false);

        $this->setDefaultQuerySettings($querySettings);
    }

    public function findOneByEntity(object $entity): ?Town
    {
        if (method_exists($entity, 'getContainedInPlaces') === false) {
            return null;
        }

        $remoteIds = ResolveForeignReference::convertToRemoteIds($entity->getContainedInPlaces());

        if ($remoteIds === []) {
            return null;
        }

        $query = $this->createQuery();

        $query->matching($query->in('remoteId', $remoteIds));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
