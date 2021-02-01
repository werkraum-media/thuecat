<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Converter;

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

use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;

class Town implements Converter
{
    private OrganisationRepository $organisationRepository;

    public function __construct(
        OrganisationRepository $organisationRepository
    ) {
        $this->organisationRepository = $organisationRepository;
    }

    public function convert(array $jsonIdOfEntity): GenericEntity
    {
        $manager = $this->organisationRepository->findOneByRemoteId($jsonIdOfEntity['thuecat:managedBy']['@id']);
        return new GenericEntity(
            95,
            'tx_thuecat_town',
            $jsonIdOfEntity['@id'],
            [
                'title' => $jsonIdOfEntity['schema:name']['@value'],
                'description' => $jsonIdOfEntity['schema:description']['@value'] ?? '',
                'managed_by' => $manager ? $manager->getUid() : 0,
            ]
        );
    }

    public function canConvert(array $type): bool
    {
        return array_search('thuecat:Town', $type) !== false;
    }
}
