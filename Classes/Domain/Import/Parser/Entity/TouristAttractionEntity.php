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
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OfferEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OpeningHoursEntity;
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
    protected string $address = '';
    protected string $url = '';
    protected string $media = '';

    public function configure(array $node, ParserContext $context): void
    {
        $language = $context->language;

        $this->remote_id = $this->getRemoteId($node);
        // Text fields (schema:name, schema:description, …) carry one entry per
        // locale; pick the one matching the site's language so the default row
        // holds the German (or configured) strings. Overlay rows for other
        // languages are the later localisation pipeline's job.
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->description = $this->extractLocalisedValue($node['schema:description'] ?? null, $language);
        $this->url = $this->extractStringValue($node['schema:url'] ?? null);

        $this->slogan = $this->extractEnumList($node['schema:slogan'] ?? null);
        $this->start_of_construction = $this->extractLocalisedValue($node['thuecat:startOfConstruction'] ?? null, $language);
        $this->sanitation = $this->extractEnumList($node['thuecat:sanitation'] ?? null);
        $this->other_service = $this->extractEnumList($node['thuecat:otherService'] ?? null);
        $this->museum_service = $this->extractEnumList($node['thuecat:museumService'] ?? null);
        $this->architectural_style = $this->extractEnumList($node['thuecat:architecturalStyle'] ?? null);
        $this->traffic_infrastructure = $this->extractEnumList($node['thuecat:trafficInfrastructure'] ?? null);
        $this->payment_accepted = $this->extractEnumList($node['schema:paymentAccepted'] ?? null);
        $this->digital_offer = $this->extractEnumList($node['thuecat:digitalOffer'] ?? null);
        $this->photography = $this->extractEnumList($node['thuecat:photography'] ?? null);
        // petsAllowed is either a localised string or a typed schema:Boolean;
        // extractLocalisedValue falls back to the plain @value for the latter.
        $this->pets_allowed = $this->extractLocalisedValue($node['schema:petsAllowed'] ?? null, $language);
        $this->is_accessible_for_free = $this->extractLocalisedValue($node['schema:isAccessibleForFree'] ?? null, $language);
        $this->public_access = $this->extractLocalisedValue($node['schema:publicAccess'] ?? null, $language);
        $this->available_languages = $this->extractEnumList($node['schema:availableLanguage'] ?? null);
        $this->distance_to_public_transport = $this->buildDistanceToPublicTransport($node['thuecat:distanceToPublicTransport'] ?? null);

        $this->opening_hours = $this->buildOpeningHours($node['schema:openingHoursSpecification'] ?? null);
        $this->special_opening_hours = $this->buildOpeningHours($node['schema:specialOpeningHoursSpecification'] ?? null);
        $this->offers = $this->buildOffers($node['schema:makesOffer'] ?? null, $language);

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

        // town, managed_by and parking_facility_near_by live on the row but stay
        // empty here — the referenced @id stubs only carry ids, not types, and
        // containedInPlace mixes cities with regions and oatour entries. The
        // resolver fetches each id's @type before choosing a target table.
        //
        // Attractions use schema:contentResponsible where TouristInformation /
        // ParkingFacility use thuecat:managedBy; both point at an organisation,
        // so we record under a single "managedBy" key and let the resolver map
        // it onto the managed_by column.
        $this->recordTransient('containedInPlace', $node['schema:containedInPlace'] ?? null);
        $this->recordTransient('managedBy', $node['thuecat:contentResponsible'] ?? null);
        $this->recordTransient('parkingFacilityNearBy', $node['thuecat:parkingFacilityNearBy'] ?? null);
        // accessibilitySpecification is a bare {"@id": "…"} stub pointing at a
        // separate resource we don't have here. The resolver fetches that
        // resource and writes the JSON blob onto the attraction's
        // accessibility_specification column — unusually for the transient
        // flow, the bucket drives a JSON blob, not a uid lookup.
        $this->recordTransient('accessibilitySpecification', $node['thuecat:accessibilitySpecification'] ?? null);
    }

    public function handlesTypes(): array
    {
        return ['schema:TouristAttraction'];
    }

    /**
     * Both schema:openingHoursSpecification and schema:specialOpeningHoursSpecification
     * arrive as a single OpeningHoursSpecification node or a list of them. Each is
     * self-contained (no @id indirection), so the transient OpeningHoursEntity can
     * shape each independently; the list of arrays is then json_encoded into the
     * single target column.
     *
     * Returns '' when the field is absent so the column stays NULL-ish rather
     * than getting a misleading "[]" literal.
     */
    private function buildOpeningHours(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $hours = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OpeningHoursEntity();
            $entity->configure($item);
            $hours[] = $entity->toArray();
        }

        if ($hours === []) {
            return '';
        }

        return (string)(json_encode($hours) ?: '');
    }

    /**
     * schema:makesOffer is a single Offer node or a list of them. Each carries
     * its own nested priceSpecification plus localised name/description, so the
     * transient OfferEntity can shape each independently; the list of arrays
     * is then json_encoded into the `offers` column.
     *
     * Returns '' when the field is absent so the column stays NULL-ish rather
     * than getting a misleading "[]" literal (keeps AbstractEntity::toArray's
     * array_filter behaviour consistent with buildOpeningHours).
     */
    private function buildOffers(mixed $value, string $language): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $offers = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OfferEntity();
            $entity->configure($item, $language);
            $offers[] = $entity->toArray();
        }

        if ($offers === []) {
            return '';
        }

        return (string)(json_encode($offers) ?: '');
    }

    /**
     * Flatten thuecat:distanceToPublicTransport into "value:unit[:mean1:mean2]".
     *
     * JSON-LD shape:
     *   schema:value       -> numeric distance
     *   schema:unitCode    -> single typed @value (e.g. thuecat:MTR)
     *   thuecat:meansOfTransport -> string | {@value} | list of either
     *
     * meansOfTransport is optional; when absent the string is just "value:unit".
     */
    private function buildDistanceToPublicTransport(mixed $node): string
    {
        if (!is_array($node)) {
            return '';
        }

        $distance = $this->extractStringValue($node['schema:value'] ?? null);
        if ($distance === '') {
            return '';
        }

        $unit = $this->extractEnumList($node['schema:unitCode'] ?? null);
        // meansOfTransport may be a single member or a list; the legacy importer
        // colon-separates every means (see Assertions fixture "350:MTR:Streetcar:CityBus"),
        // so we flatten the members into sibling parts rather than comma-joining.
        $means = $this->extractEnumMembers($node['thuecat:meansOfTransport'] ?? null);

        $parts = array_merge([$distance, $unit], $means);

        return implode(':', array_filter($parts, static fn ($part) => $part !== ''));
    }
}
