<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\QuantityNormaliser;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Import\Reconciliation\FieldMechanism;

/**
 * A meeting place stays an attraction, so it writes the attraction table as its own record type.
 * Payloads carry the full class chain, so subclasses of MeetingCongress are claimed through it.
 */
class MeetingVenueEntity extends TouristAttractionEntity
{
    public const RECONCILED_FIELDS = [
        'type' => FieldMechanism::Scalar,
        'title' => FieldMechanism::Scalar,
        'description' => FieldMechanism::Scalar,
        'slogan' => FieldMechanism::Scalar,
        'start_of_construction' => FieldMechanism::Scalar,
        'sanitation' => FieldMechanism::Scalar,
        'other_service' => FieldMechanism::Scalar,
        'museum_service' => FieldMechanism::Scalar,
        'architectural_style' => FieldMechanism::Scalar,
        'traffic_infrastructure' => FieldMechanism::Scalar,
        'payment_accepted' => FieldMechanism::Scalar,
        'digital_offer' => FieldMechanism::Scalar,
        'photography' => FieldMechanism::Scalar,
        'pets_allowed' => FieldMechanism::Scalar,
        'is_accessible_for_free' => FieldMechanism::Scalar,
        'public_access' => FieldMechanism::Scalar,
        'available_languages' => FieldMechanism::Scalar,
        'distance_to_public_transport' => FieldMechanism::Scalar,
        'offers' => FieldMechanism::Scalar,
        'url' => FieldMechanism::Scalar,
        'venue_type' => FieldMechanism::Scalar,
        'meeting_facilities' => FieldMechanism::Scalar,
        'gastro' => FieldMechanism::Scalar,
        'serves_cuisine' => FieldMechanism::Scalar,
        'entrance' => FieldMechanism::Scalar,
        'guided_tour' => FieldMechanism::Scalar,
        'monument' => FieldMechanism::Scalar,
        'ensemble' => FieldMechanism::Scalar,
        'exhibition_objects' => FieldMechanism::Scalar,
        'sports_equipment' => FieldMechanism::Scalar,
        'amenity_features' => FieldMechanism::Scalar,
        'price_range' => FieldMechanism::Scalar,
        'star_rating' => FieldMechanism::Scalar,
        'smoking_allowed' => FieldMechanism::Scalar,
        'is_ruin' => FieldMechanism::Scalar,
        'open_air_museum' => FieldMechanism::Scalar,
        'bookable' => FieldMechanism::Scalar,
        'has_drive_through_service' => FieldMechanism::Scalar,
        'beer_garden' => FieldMechanism::Scalar,
        'catering' => FieldMechanism::Scalar,
        'delivery_service' => FieldMechanism::Scalar,
        'hot_cuisine_throughout' => FieldMechanism::Scalar,
        'late_kitchen' => FieldMechanism::Scalar,
        'self_service' => FieldMechanism::Scalar,
        'take_away' => FieldMechanism::Scalar,
        'accepts_reservations' => FieldMechanism::Scalar,
        'maximum_attendee_capacity' => FieldMechanism::Scalar,
        'number_of_rooms' => FieldMechanism::Scalar,
        'single_rooms' => FieldMechanism::Scalar,
        'double_rooms' => FieldMechanism::Scalar,
        'twin_rooms' => FieldMechanism::Scalar,
        'suites' => FieldMechanism::Scalar,
        'seats_inside' => FieldMechanism::Scalar,
        'seats_outside' => FieldMechanism::Scalar,
        'distance_airport' => FieldMechanism::Scalar,
        'distance_city_center' => FieldMechanism::Scalar,
        'distance_fair' => FieldMechanism::Scalar,
        'distance_freeway' => FieldMechanism::Scalar,
        'distance_ice_station' => FieldMechanism::Scalar,
        'size_of_area' => FieldMechanism::Scalar,
        'checkin_time' => FieldMechanism::Scalar,
        'checkout_time' => FieldMechanism::Scalar,
        'has_map' => FieldMechanism::Scalar,
        'tour_booking_page' => FieldMechanism::Scalar,
        'identifier' => FieldMechanism::Scalar,
        'same_as' => FieldMechanism::Scalar,
        'subject_of' => FieldMechanism::Scalar,
        'typical_age_range' => FieldMechanism::Scalar,
        'end_of_construction' => FieldMechanism::Scalar,
        'certifications' => FieldMechanism::Scalar,
        'description_culture_portal' => FieldMechanism::Scalar,
        'description_lodging_meeting' => FieldMechanism::Scalar,
        'categories' => FieldMechanism::RelationReplace,
        'keywords' => FieldMechanism::RelationReplace,
        'main_image' => FieldMechanism::ExplicitDelete,
        'media_files' => FieldMechanism::ExplicitDelete,
        'logo' => FieldMechanism::ExplicitDelete,
        'subject_of_media' => FieldMechanism::ExplicitDelete,
        'opening_hours_inline' => FieldMechanism::ExplicitDelete,
        'special_opening_hours_inline' => FieldMechanism::ExplicitDelete,
        'address_inline' => FieldMechanism::ExplicitDelete,
        'parking_facilities_inline' => FieldMechanism::ExplicitDelete,
        'town' => FieldMechanism::ReferenceWrite,
        'contained_in_organisation' => FieldMechanism::ReferenceWrite,
        'contained_in_attraction' => FieldMechanism::ReferenceWrite,
        'contained_in_place' => FieldMechanism::ReferenceWrite,
        'contained_in_tourist_information' => FieldMechanism::ReferenceWrite,
        'contained_in_parking_facility' => FieldMechanism::ReferenceWrite,
        'contained_in_trail' => FieldMechanism::ReferenceWrite,
        'managed_by' => FieldMechanism::ReferenceWrite,
        'operated_by' => FieldMechanism::ReferenceWrite,
        'geo_touches_trail' => FieldMechanism::ReferenceWrite,
        'managed_objects' => FieldMechanism::ReferenceWrite,
        'parking_facility_near_by' => FieldMechanism::ReferenceWrite,
        'meeting_rooms' => FieldMechanism::ReferenceWrite,
        'tourist_types' => FieldMechanism::ReferenceWrite,
        'who_was_here' => FieldMechanism::ReferenceWrite,
        'architects' => FieldMechanism::ReferenceWrite,
        'accessibility_specification' => FieldMechanism::ReferenceWrite,
    ];

    // Named, never fetched: the referenced events arrive through their own imports.
    public const HOSTED_EVENTS_BUCKET = 'hostedEvents';

    public const ROOMS_BUCKET = 'meetingRooms';

    // Upstream property => transient bucket.
    protected const REFERENCES = [
        'thuecat:managedBy' => 'operatedBy',
        'schema:geoTouches' => 'geoTouches',
        'thuecat:managedObjects' => 'managedObjects',
        'schema:event' => self::HOSTED_EVENTS_BUCKET,
        'schema:touristType' => 'touristType',
        'thuecat:whoWasHere' => 'whoWasHere',
        'thuecat:architect' => 'architect',
    ];

    // Lists and single members alike: both store comma-joined local names.
    protected const ENUMS = [
        'meeting_facilities' => 'thuecat:meetingFacilitysHouse',
        'gastro' => 'thuecat:gastro',
        'serves_cuisine' => 'schema:servesCuisine',
        'entrance' => 'thuecat:entrance',
        'guided_tour' => 'thuecat:guidedTour',
        'monument' => 'thuecat:monumentEnum',
        'ensemble' => 'thuecat:ensemble',
        'exhibition_objects' => 'thuecat:exibitionObjekt',
        'sports_equipment' => 'thuecat:sportsEquipment',
        'price_range' => 'schema:priceRange',
    ];

    protected const BOOLEANS = [
        'smoking_allowed' => 'schema:smokingAllowed',
        'is_ruin' => 'thuecat:isRuin',
        'open_air_museum' => 'thuecat:openAirMuseum',
        'bookable' => 'thuecat:bookable',
        'has_drive_through_service' => 'schema:hasDriveThroughService',
        'beer_garden' => 'thuecat:beerGarden',
        'catering' => 'thuecat:catering',
        'delivery_service' => 'thuecat:deliveryService',
        'hot_cuisine_throughout' => 'thuecat:hotCuisineThroughout',
        'late_kitchen' => 'thuecat:lateKitchen',
        'self_service' => 'thuecat:selfService',
        'take_away' => 'thuecat:takeAway',
        'accepts_reservations' => 'schema:acceptsReservations',
    ];

    protected const INTEGERS = [
        'maximum_attendee_capacity' => 'schema:maximumAttendeeCapacity',
        'number_of_rooms' => 'schema:numberOfRooms',
        'single_rooms' => 'thuecat:singleRoomsInHouse',
        'double_rooms' => 'thuecat:doubleRoomsInHouse',
        'twin_rooms' => 'thuecat:twinRoomsInHouse',
        'suites' => 'thuecat:suitenInHouse',
        'seats_inside' => 'thuecat:seatsInside',
        'seats_outside' => 'thuecat:seatsOutside',
    ];

    protected const QUANTITIES = [
        'distance_airport' => 'thuecat:distanceAirport',
        'distance_city_center' => 'thuecat:distanceCityCenter',
        'distance_fair' => 'thuecat:distanceFair',
        'distance_freeway' => 'thuecat:distanceFreeway',
        'distance_ice_station' => 'thuecat:distanceICECStation',
        'size_of_area' => 'thuecat:sizeOfArea',
    ];

    protected const SCALARS = [
        'checkin_time' => 'schema:checkinTime',
        'checkout_time' => 'schema:checkoutTime',
    ];

    protected const URL_LISTS = [
        'has_map' => 'schema:hasMap',
        'tour_booking_page' => 'schema:tourBookingPage',
        'identifier' => 'schema:identifier',
        'same_as' => 'schema:sameAs',
    ];

    protected const LOCALISED = [
        'typical_age_range' => 'schema:typicalAgeRange',
        'end_of_construction' => 'thuecat:endOfConstruction',
    ];

    protected const DESCRIPTION_TYPES = [
        'thuecat:DescriptionCulturePortal' => 'description_culture_portal',
        'thuecat:LodgingMeetingDescription' => 'description_lodging_meeting',
    ];

    protected int $priority = 30;

    protected string $type = 'meeting_venue';

    protected string $venue_type = '';

    protected string $meeting_facilities = '';

    protected string $gastro = '';

    protected string $serves_cuisine = '';

    protected string $entrance = '';

    protected string $guided_tour = '';

    protected string $monument = '';

    protected string $ensemble = '';

    protected string $exhibition_objects = '';

    protected string $sports_equipment = '';

    protected string $amenity_features = '';

    protected string $price_range = '';

    protected string $star_rating = '';

    protected string $smoking_allowed = '';

    protected string $is_ruin = '';

    protected string $open_air_museum = '';

    protected string $bookable = '';

    protected string $has_drive_through_service = '';

    protected string $beer_garden = '';

    protected string $catering = '';

    protected string $delivery_service = '';

    protected string $hot_cuisine_throughout = '';

    protected string $late_kitchen = '';

    protected string $self_service = '';

    protected string $take_away = '';

    protected string $accepts_reservations = '';

    protected int $maximum_attendee_capacity = 0;

    protected int $number_of_rooms = 0;

    protected int $single_rooms = 0;

    protected int $double_rooms = 0;

    protected int $twin_rooms = 0;

    protected int $suites = 0;

    protected int $seats_inside = 0;

    protected int $seats_outside = 0;

    protected int $distance_airport = 0;

    protected int $distance_city_center = 0;

    protected int $distance_fair = 0;

    protected int $distance_freeway = 0;

    protected int $distance_ice_station = 0;

    protected int $size_of_area = 0;

    protected string $checkin_time = '';

    protected string $checkout_time = '';

    protected string $has_map = '';

    protected string $tour_booking_page = '';

    protected string $identifier = '';

    protected string $same_as = '';

    protected string $subject_of = '';

    protected string $typical_age_range = '';

    protected string $end_of_construction = '';

    protected string $certifications = '';

    protected string $description_culture_portal = '';

    protected string $description_lodging_meeting = '';

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        parent::parse($node, $language, $parserContext, $translationLanguages);
        $this->buildParkingFacilities($node, $language, $parserContext, $translationLanguages);

        $types = is_array($node['@type'] ?? null) ? $node['@type'] : [];
        $this->venue_type = in_array('thuecat:MeetingCongressHotel', $types, true)
            ? 'meeting_congress_hotel'
            : 'meeting_congress';

        foreach (self::ENUMS as $field => $property) {
            $this->$field = $this->extractConcatenatedString($node[$property] ?? null, $language);
        }

        $this->amenity_features = implode(',', array_filter(array_map(
            fn (array $feature): string => $this->stripNamespacePrefix($this->extractValue($feature['thuecat:featureName'] ?? null, $language)),
            $this->nodesOf($node['schema:amenityFeature'] ?? null)
        )));
        $this->star_rating = $this->stripNamespacePrefix(
            $this->extractValue($this->nodesOf($node['schema:starRating'] ?? null)[0]['schema:ratingValue'] ?? null, $language)
        );
        foreach ([...self::BOOLEANS, ...self::SCALARS] as $field => $property) {
            $this->$field = $this->extractValue($node[$property] ?? null, $language);
        }

        foreach (self::INTEGERS as $field => $property) {
            $this->$field = (int)$this->extractValue($node[$property] ?? null, $language);
        }

        foreach (self::QUANTITIES as $field => $property) {
            $this->$field = $this->normalisedQuantity($node, $property, $parserContext);
        }

        foreach (self::URL_LISTS as $field => $property) {
            $this->$field = implode("\n", $this->extractUrls($node[$property] ?? null, $language));
        }

        $this->recordMediaOfKind('logo', $node['schema:logo'] ?? null);
        $this->recordMediaOfKind('subjectOf', $this->mediaReferences($node['schema:subjectOf'] ?? null));
        $this->subject_of = implode("\n", $this->pageReferences($node['schema:subjectOf'] ?? null));
        foreach (self::REFERENCES as $property => $bucket) {
            $this->recordTransient($bucket, $node[$property] ?? null);
        }

        // Embedded nodes are hotel rooms, never meeting rooms.
        $this->recordTransient(self::ROOMS_BUCKET, $this->bareReferencesOnly($node['schema:containsPlace'] ?? null));

        $this->parseLocalised($node, $language, null, $parserContext);
        foreach ($translationLanguages as $code => $sysLanguageUid) {
            // A language without own content gets no row, as in the attraction's parse.
            if (isset($this->translations[$sysLanguageUid])) {
                $this->parseLocalised($node, $code, $sysLanguageUid, $parserContext);
            }
        }
    }

    public function handlesTypes(): array
    {
        return ['thuecat:MeetingCongressHotel', 'thuecat:MeetingCongress'];
    }

    /**
     * The parser emits one level of children, so the facilities' own children are handed on.
     *
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    protected function buildParkingFacilities(array $node, string $language, ParserContext $parserContext, array $translationLanguages): void
    {
        foreach ($this->nodesOf($node['thuecat:parkingFacility'] ?? null) as $ordinal => $parkingNode) {
            $parkingFacility = new ParkingFacilityEntity();
            $parkingFacility->configureInline($parkingNode, $language, $parserContext, $translationLanguages, $this->remote_id, $ordinal);
            $this->children[] = $parkingFacility;
            array_push($this->children, ...$parkingFacility->getChildren());
        }
    }

    /**
     * Fills the default row when $sysLanguageUid is null, the translation of that language otherwise.
     *
     * @param array<string, mixed> $node
     */
    protected function parseLocalised(array $node, string $language, ?int $sysLanguageUid, ParserContext $parserContext): void
    {
        $values = [];
        foreach (self::LOCALISED as $field => $property) {
            $values[$field] = $this->extractValue($node[$property] ?? null, $language);
        }

        $values['certifications'] = implode("\n", array_filter(array_map(
            fn (array $certification): string => $this->extractValue($certification['schema:name'] ?? null, $language),
            $this->nodesOf($node['schema:hasCertification'] ?? null)
        )));
        foreach (self::DESCRIPTION_TYPES as $field) {
            $values[$field] = '';
        }

        foreach ($this->nodesOf($node['thuecat:additionalDescriptions'] ?? null) as $description) {
            $field = $this->descriptionField($description);
            if ($field === null) {
                // Reported once, from the default-language pass.
                if ($sysLanguageUid === null) {
                    $this->reportUnmatched($parserContext, 'thuecat:additionalDescriptions', $this->descriptionType($description));
                }

                continue;
            }

            $text = $this->extractDescription($description, $language);
            $values[$field] = $values[$field] === '' ? $text : $values[$field] . "\n\n" . $text;
        }

        foreach ($values as $field => $value) {
            if ($sysLanguageUid === null) {
                $this->$field = $value;
            } else {
                $this->recordTranslation($field, $value, $sysLanguageUid);
            }
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    protected function normalisedQuantity(array $node, string $property, ParserContext $parserContext): int
    {
        $quantity = $this->nodesOf($node[$property] ?? null)[0] ?? null;
        if ($quantity === null) {
            return 0;
        }

        $normaliser = new QuantityNormaliser();
        $value = $normaliser->normalise($quantity);
        if ($value === null) {
            $this->reportUnmatched($parserContext, $property, $normaliser->unitOf($quantity));
            return 0;
        }

        return (int)round($value);
    }

    /**
     * @param array<string, mixed> $description
     */
    protected function descriptionField(array $description): ?string
    {
        foreach ((array)($description['@type'] ?? []) as $type) {
            if (is_string($type) && isset(self::DESCRIPTION_TYPES[$type])) {
                return self::DESCRIPTION_TYPES[$type];
            }
        }

        return null;
    }

    /**
     * The additionalType names the specific text type, the @type list only its class chain.
     *
     * @param array<string, mixed> $description
     */
    protected function descriptionType(array $description): string
    {
        $additionalType = $description['schema:additionalType'] ?? null;
        if (is_array($additionalType) && is_string($additionalType['@id'] ?? null)) {
            return $additionalType['@id'];
        }

        $types = array_values(array_filter((array)($description['@type'] ?? []), is_string(...)));

        return $types === [] ? '' : $types[array_key_last($types)];
    }

    /**
     * subjectOf mixes media with pages about the place; only the media go the media path.
     *
     * @return list<array<string, mixed>>
     */
    protected function mediaReferences(mixed $value): array
    {
        return array_values(array_filter(
            $this->nodesOf($value),
            static fn (array $reference): bool => is_string($reference['@id'] ?? null)
                && str_contains($reference['@id'], '/dms_')
        ));
    }

    /**
     * @return list<string>
     */
    protected function pageReferences(mixed $value): array
    {
        $ids = [];
        foreach ($this->nodesOf($value) as $reference) {
            $id = $reference['@id'] ?? null;
            if (is_string($id) && !str_contains($id, '/dms_')) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * URLs keep their colon, which the enum lists strip as a namespace prefix.
     *
     * @return list<string>
     */
    protected function extractUrls(mixed $value, string $language): array
    {
        if (in_array($value, [null, '', []], true)) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];

        return array_values(array_filter(array_map(
            fn (mixed $item): string => $this->extractValue($item, $language),
            $items
        )));
    }
}
