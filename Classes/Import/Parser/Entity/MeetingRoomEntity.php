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

use WerkraumMedia\ThueCat\Import\Parser\Entity\Places\Support\PlaceCategoryMapper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\QuantityNormaliser;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * A meeting room is reached only through the venue or the room naming it, never as an import root. It relates the
 * rooms its own schema:containsPlace names, as delivered; its schema:containedInPlace is not stored.
 */
class MeetingRoomEntity extends AbstractPlaceEntity
{
    public const TABLE = 'tx_thuecat_meeting_room';

    public const ROOMS_BUCKET = 'containedRooms';

    public const MEDIA_FIELDS = [
        'photo' => 'main_image',
        'image' => 'media_files',
        'floorPlan' => 'floor_plan',
    ];

    protected const INTEGERS = [
        'capacity_banquet' => 'thuecat:capacityBanquetSeating',
        'capacity_classroom' => 'thuecat:capacityClassroomSeating',
        'capacity_theatre' => 'thuecat:capacityTheatreSeating',
        'capacity_standing' => 'thuecat:capacityStandUpReception',
        'number_of_rooms' => 'schema:numberOfRooms',
    ];

    // m² and m, as delivered
    protected const DECIMALS = [
        'floor_size' => 'schema:floorSize',
        'room_height' => 'thuecat:roomHeight',
    ];

    protected int $priority = 20;
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';
    protected string $url = '';
    protected string $floor_size = '';
    protected string $room_height = '';
    protected int $capacity_banquet = 0;
    protected int $capacity_classroom = 0;
    protected int $capacity_theatre = 0;
    protected int $capacity_standing = 0;
    protected string $facilities = '';
    protected int $number_of_rooms = 0;
    protected string $amenity_features = '';
    protected string $tour_booking_page = '';
    protected string $bookable = '';

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->remote_id = $this->getRemoteId($node);

        $localisedFields = [
            'title' => 'schema:name',
            'description' => 'schema:description',
        ];
        foreach ($localisedFields as $field => $jsonldName) {
            $this->$field = $this->extractValue($node[$jsonldName] ?? null, $language);
        }
        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $this->recordTranslation($field, $this->extractValue($node[$jsonldName] ?? null, $code), $sysLanguageUid);
            }
        }

        foreach (self::INTEGERS as $field => $property) {
            $this->$field = (int)$this->extractValue($node[$property] ?? null, $language);
        }
        foreach (self::DECIMALS as $field => $property) {
            $this->$field = $this->quantity($node, $property, $parserContext);
        }
        $this->facilities = $this->extractConcatenatedString($node['thuecat:meetingFacilitysRoom'] ?? null, $language);
        $this->amenity_features = implode(',', array_filter(array_map(
            fn (array $feature): string => $this->stripNamespacePrefix($this->extractValue($feature['thuecat:featureName'] ?? null, $language)),
            $this->nodesOf($node['schema:amenityFeature'] ?? null)
        )));
        $this->bookable = $this->extractValue($node['thuecat:bookable'] ?? null, $language);
        $this->url = $this->extractValue($node['schema:url'] ?? null, $language);
        $this->tour_booking_page = $this->extractValue($node['schema:tourBookingPage'] ?? null, $language);

        $this->recordTransient('managedBy', $node['thuecat:contentResponsible'] ?? null);
        $this->recordTransient(self::ROOMS_BUCKET, $this->bareReferencesOnly($node['schema:containsPlace'] ?? null));
        $this->recordMediaTransient($node['schema:photo'] ?? null, $node['schema:image'] ?? null, null);
        $this->recordFloorPlan($node, $parserContext);

        $this->applyCategoryMapper(new PlaceCategoryMapper(), $node);
        $this->recordKeywords($node);
    }

    public function handlesTypes(): array
    {
        return ['schema:MeetingRoom'];
    }

    /**
     * @param array<string, mixed> $node
     */
    protected function quantity(array $node, string $property, ParserContext $parserContext): string
    {
        $quantity = $this->nodesOf($node[$property] ?? null)[0] ?? null;
        if ($quantity === null) {
            return '';
        }

        $normaliser = new QuantityNormaliser();
        $value = $normaliser->normalise($quantity);
        if ($value === null) {
            $this->reportUnmatched($parserContext, $property, $normaliser->unitOf($quantity));
            return '';
        }

        return (string)$value;
    }

    /**
     * The layout image wins over the plain image; both name the same media where both are delivered.
     *
     * @param array<string, mixed> $node
     */
    protected function recordFloorPlan(array $node, ParserContext $parserContext): void
    {
        $floorPlan = $this->nodesOf($node['schema:accommodationFloorPlan'] ?? null)[0] ?? null;
        if ($floorPlan === null) {
            return;
        }

        $image = $this->nodesOf($floorPlan['schema:layoutImage'] ?? null)[0]
            ?? $this->nodesOf($floorPlan['schema:image'] ?? null)[0]
            ?? null;
        $id = is_array($image) && is_string($image['@id'] ?? null) ? $image['@id'] : '';
        if ($id === '') {
            return;
        }
        if (!str_contains($id, '/dms_')) {
            $this->reportUnmatched($parserContext, 'schema:accommodationFloorPlan', $id);
            return;
        }

        $this->recordMediaOfKind('floorPlan', ['@id' => $id]);
    }
}
