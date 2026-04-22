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

// Transient: never registered as `import.entity` tagged service, never dispatched
// by the Parser. Lifecycle is owned by the parent (TouristAttraction et al.) which
// constructs, configures, and json_encodes it into one of its own fields.
class AddressEntity
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

    public function toArray(): array
    {
        $array = get_object_vars($this);
        unset($array['table']);

        return array_filter($array);
    }

    protected function getRemoteId(array $node): string
    {
        return (string)($node['@id'] ?? '');
    }

    protected function extractLanguageValue(mixed $value): string
    {
        if (is_array($value) && isset($value['@value'])) {
            return (string)$value['@value'];
        }

        return '';
    }

    protected function extractStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string)($value['@value'] ?? '');
        }

        return '';
    }

    protected function extractGeo(array $geoNode): array
    {
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
}
