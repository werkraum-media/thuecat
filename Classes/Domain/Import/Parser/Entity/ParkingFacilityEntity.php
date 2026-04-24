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

// Column set is narrower than TouristAttraction (no url, no slogan, no
// accessibility_specification, no pets/public_access/…); see TCA
// tx_thuecat_parking_facility. buildOpeningHours / buildOffers /
// buildDistanceToPublicTransport / collectIds live on AbstractEntity.
class ParkingFacilityEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_parking_facility';
    // Higher than the default 10 — ParkingFacility nodes also carry
    // schema:Organization in @type, so without priority the generic
    // OrganisationEntity would win the resolver tie-break.
    protected int $priority = 20;
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';
    protected string $sanitation = '';
    protected string $other_service = '';
    protected string $traffic_infrastructure = '';
    protected string $payment_accepted = '';
    protected string $distance_to_public_transport = '';
    protected string $opening_hours = '';
    protected string $special_opening_hours = '';
    protected string $offers = '';
    protected string $address = '';

    public function configure(array $node, string $language): void
    {
        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->description = $this->extractLocalisedValue($node['schema:description'] ?? null, $language);

        $this->sanitation = $this->extractEnumList($node['thuecat:sanitation'] ?? null);
        $this->other_service = $this->extractEnumList($node['thuecat:otherService'] ?? null);
        $this->traffic_infrastructure = $this->extractEnumList($node['thuecat:trafficInfrastructure'] ?? null);
        $this->payment_accepted = $this->extractEnumList($node['schema:paymentAccepted'] ?? null);
        $this->distance_to_public_transport = $this->buildDistanceToPublicTransport($node['thuecat:distanceToPublicTransport'] ?? null);

        $this->opening_hours = $this->buildOpeningHours($node['schema:openingHoursSpecification'] ?? null);
        $this->special_opening_hours = $this->buildOpeningHours($node['schema:specialOpeningHoursSpecification'] ?? null);
        $this->offers = $this->buildOffers($node['schema:makesOffer'] ?? null, $language);

        if (!empty($node['schema:address'])) {
            $address = new AddressEntity();
            $address->configure(
                $node['schema:address'],
                $node['schema:geo'] ?? []
            );
            $this->address = (string)(json_encode($address->toArray()) ?: '');
        }

        // town and managed_by live on the row but stay empty here — the
        // referenced @id stubs only carry ids, not types, so the resolver
        // must look each one up before deciding which table it points to.
        // ParkingFacility uses thuecat:managedBy directly (unlike attractions,
        // which encode the same relation as thuecat:contentResponsible).
        $this->recordTransient('containedInPlace', $node['schema:containedInPlace'] ?? null);
        $this->recordTransient('managedBy', $node['thuecat:managedBy'] ?? null);

        // schema:image / schema:photo / schema:video are bare {"@id": "dms_…"}
        // stubs pointing at separate resources we don't have here. Merge all
        // three slots into a single "media" bucket; the resolver fetches each
        // dms_* resource, shapes it into the legacy Media frontend model's JSON
        // and writes the blob onto the parking facility's media column.
        $mediaRefs = array_merge(
            $this->collectIds($node['schema:image'] ?? null),
            $this->collectIds($node['schema:photo'] ?? null),
            $this->collectIds($node['schema:video'] ?? null),
        );
        if ($mediaRefs !== []) {
            $this->recordTransient('media', $mediaRefs);
        }
    }

    public function handlesTypes(): array
    {
        return ['schema:ParkingFacility'];
    }
}
