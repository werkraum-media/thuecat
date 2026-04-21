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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;

class OrganisationEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_organisation';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    // Relations, track by their identifier
    protected string $towns = '';
    protected string $manages_tourist_information = '';
    protected string $manages_tourist_attraction = '';

    public function configure(array $node, bool $extractRelations = false)
    {
        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLanguageValue($node['schema:name'] ?? null);
        $this->description = $this->extractLanguageValue($node['schema:description'] ?? null);
        if ($extractRelations === true) {
            // @todo [1] implement relation extraction, if this is the top level entity.
            // @todo [2] For now, we skip, as everything comes from elsewhere (TouristAttraction mostly)
        }

    }

    public function handlesTypes():array
    {
        return [
            'schema:Organization',
        ];
    }

}
