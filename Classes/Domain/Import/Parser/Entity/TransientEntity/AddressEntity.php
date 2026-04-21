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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity;

use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\AbstractEntity;

class AddressEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_address';

    protected string $remote_id = '';
    protected string $street = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $email = '';
    protected string $phone = '';
    protected string $fax = '';
    protected array $geo = [];

    public function configure(array $node, array $geo_node = []): void
    {
        $this->remote_id = $this->getRemoteId($node);
        $this->street = $this->extractLanguageValue($node['schema:streetAddress'] ?? null);
        $this->zip = $this->extractLanguageValue($node['schema:postalCode'] ?? null);
        $this->city = $this->extractLanguageValue($node['schema:addressLocality'] ?? null);
        $this->email = $this->extractLanguageValue($node['schema:email'] ?? null);
        $this->phone = $this->extractLanguageValue($node['schema:telephone'] ?? null);
        $this->fax = $this->extractLanguageValue($node['schema:faxNumber'] ?? null);
        if ($geo_node) {
            $this->geo = $this->extractGeo($geo_node);
        }
    }

    protected function extractGeo(array $geoNode): array
    {
        if (!is_array($geoNode)) {
            return [];
        }

        $latitude = $this->extractStringValue($geoNode['schema:latitude'] ?? null);
        $longitude = $this->extractStringValue($geoNode['schema:longitude'] ?? null);

        if ($latitude === '' || $longitude === '') {
            return [];
        }

        return [
            'latitude' => (float)$latitude,
            'longitude' => (float)$longitude,
        ];
    }

    public function handlesTypes():array
    {
        return [];
    }
}
