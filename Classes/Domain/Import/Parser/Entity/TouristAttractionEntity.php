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

class TouristAttractionEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_tourist_attraction';
    protected int $priority = 20;
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

    public function parse(array $node, string $language, array $translationLanguages = []): void
    {
        $this->translations = [];
        $this->remote_id = $this->getRemoteId($node);

        // Localised text fields (schema:name, schema:description, …): pick the
        // entry matching the site's default language for the main row, then
        // probe each translation language so per-locale values land in the
        // translations bucket. petsAllowed is included here because in
        // practice ThueCat publishes it as a localised string ("Tiere sind
        // im Gebäude nicht gestattet …") even though the schema technically
        // allows a typed boolean.
        // Localised text fields (schema:name, schema:description, …): pick the
        // entry matching the site's default language for the main row, then
        // probe each translation language so per-locale values land in the
        // translations bucket. petsAllowed is included here because in
        // practice ThueCat publishes it as a localised string ("Tiere sind
        // im Gebäude nicht gestattet …") even though the schema technically
        // allows a typed boolean.
        //
        // The "concatenated" set treats URI-list values (slogan, sanitation,
        // paymentAccepted, …) the same way: extract once per language and
        // record translations alongside the main row, even though current
        // ThueCat fixtures emit lang-less values that resolve identically
        // across locales.
        $localisedFields = [
            'title' => 'schema:name',
            'description' => 'schema:description',
            'start_of_construction' => 'thuecat:startOfConstruction',
            'pets_allowed' => 'schema:petsAllowed',
        ];
        $concatenatedFields = [
            'slogan' => 'schema:slogan',
            'sanitation' => 'thuecat:sanitation',
            'other_service' => 'thuecat:otherService',
            'museum_service' => 'thuecat:museumService',
            'architectural_style' => 'thuecat:architecturalStyle',
            'traffic_infrastructure' => 'thuecat:trafficInfrastructure',
            'payment_accepted' => 'schema:paymentAccepted',
            'digital_offer' => 'thuecat:digitalOffer',
            'photography' => 'thuecat:photography',
            'available_languages' => 'schema:availableLanguage',
        ];
        foreach ($localisedFields as $field => $jsonldName) {
            $this->$field = $this->extractLocalisedValue($node[$jsonldName] ?? null, $language);
        }
        foreach ($concatenatedFields as $field => $jsonldName) {
            $this->$field = $this->extractConcatenatedString($node[$jsonldName] ?? null, $language);
        }
        $this->distance_to_public_transport = $this->buildDistanceToPublicTransport(
            $node['thuecat:distanceToPublicTransport'] ?? null,
            $language
        );
        $this->offers = $this->buildOffers($node['schema:makesOffer'] ?? null, $language);

        // Two-pass: localised fields decide whether a language is "present"
        // (a language with no @language-tagged content does not get a row).
        // Only languages that picked up at least one localised field then
        // receive the lang-less concatenated/derived fields, so a site with
        // fr enabled but no fr content in the JSON-LD does not spawn an
        // empty fr translation row driven purely by URI-list values.
        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $value = $this->extractLocalisedValue($node[$jsonldName] ?? null, $code);
                $this->recordTranslation($field, $value, $sysLanguageUid);
            }
            if (!isset($this->translations[$sysLanguageUid])) {
                continue;
            }
            foreach ($concatenatedFields as $field => $jsonldName) {
                $value = $this->extractConcatenatedString($node[$jsonldName] ?? null, $code);
                $this->recordTranslation($field, $value, $sysLanguageUid);
            }
            $distance = $this->buildDistanceToPublicTransport(
                $node['thuecat:distanceToPublicTransport'] ?? null,
                $code
            );
            $this->recordTranslation('distance_to_public_transport', $distance, $sysLanguageUid);
            $offers = $this->buildOffers($node['schema:makesOffer'] ?? null, $code);
            $this->recordTranslation('offers', $offers, $sysLanguageUid);
        }

        $this->url = $this->extractStringValue($node['schema:url'] ?? null);
        // schema:isAccessibleForFree / schema:publicAccess are typed
        // schema:Boolean values with no @language tag — extractStringValue
        // pulls the bare @value without going through the localised path.
        $this->is_accessible_for_free = $this->extractStringValue($node['schema:isAccessibleForFree'] ?? null);
        $this->public_access = $this->extractStringValue($node['schema:publicAccess'] ?? null);

        $this->opening_hours = $this->buildOpeningHours($node['schema:openingHoursSpecification'] ?? null);
        $this->special_opening_hours = $this->buildOpeningHours($node['schema:specialOpeningHoursSpecification'] ?? null);

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

        // schema:photo / schema:image / schema:video are bare {"@id": "dms_…"}
        // stubs pointing at separate resources we don't have here. The resolver
        // fetches each dms_* resource, shapes it into the legacy Media frontend
        // model's JSON and writes the blob onto the attraction's media column.
        // The per-ref `kind` tag tells the resolver which source slot each
        // entry came from so mainImage + type end up correct on output. Same
        // fetch-and-shape path as accessibilitySpecification, just list-shaped.
        $this->recordMediaTransient(
            $node['schema:photo'] ?? null,
            $node['schema:image'] ?? null,
            $node['schema:video'] ?? null,
        );
    }

    public function handlesTypes(): array
    {
        return ['schema:TouristAttraction'];
    }
}
