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

use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\AddressEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

class TouristAttractionEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_tourist_attraction';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';
    protected string $slogan = '';
    protected string $start_of_construction = '';
    protected string $sanitation = '';
    protected string $other_service = '';
    protected string $museum_service = '';
    protected string $architectural_style = '';
    protected string $traffic_infrastructure = '';
    protected string $payment_accepted = '';
    protected string $digital_offer = '';
    protected string $photography = '';
    protected string $pets_allowed = '';
    protected string $is_accessible_for_free = '';
    protected string $public_access = '';
    protected string $available_languages = '';
    protected string $distance_to_public_transport = '';
    protected string $opening_hours = '';
    protected string $special_opening_hours = '';
    protected string $offers = '';
    protected string $accessibility_specification = '';
    protected string $address = '';
    protected string $url = '';
    protected string $media = '';

    // Relations — REF:<remote_id> (comma-joined if multi-value).
    protected string $town = '';
    protected string $managed_by = '';
    protected string $parking_facility_near_by = '';

    public function configure(array $node, ParserContext $context): void
    {
        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLanguageValue($node['schema:name'] ?? null);
        $this->description = $this->extractLanguageValue($node['schema:description'] ?? null);
        $this->url = $this->extractStringValue($node['schema:url'] ?? null);

        if (!empty($node['schema:address'])) {
            // Address + geo are one logical record in TCA but two sibling keys in
            // JSON-LD. The transient AddressEntity merges them into a single JSON
            // blob stored on this entity's `address` column.
            $address = new AddressEntity();
            $address->configure(
                $node['schema:address'],
                $node['schema:geo'] ?? []
            );
            $this->address = (string)(json_encode($address->toArray()) ?: '');
        }
    }

    public function handlesTypes(): array
    {
        return ['schema:TouristAttraction'];
    }
}
