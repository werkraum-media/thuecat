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

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class ParkingFacilityEntity extends AbstractPlaceEntity
{
    public const TABLE = 'tx_thuecat_parking_facility';

    // Inline nodes carry a blank-node @id, so their identity is derived from the venue.
    public const INLINE_SEPARATOR = '::parking::';

    protected const VEHICLE_TYPE_FIELDS = [
        'thuecat:Car' => 'spaces_car',
        'thuecat:HandicappedParking' => 'spaces_handicapped',
        'thuecat:BusOrCoach' => 'spaces_bus',
        'thuecat:ElectricVehicleCarChargingStationEnumMem' => 'spaces_ev_charging',
        'thuecat:Motorcycle' => 'spaces_motorcycle',
    ];

    public const MEDIA_FIELDS = [
        'photo' => 'main_image',
        'image' => 'media_files',
    ];

    // OrganisationEntity would win the resolver tie-break if lower
    protected int $priority = 30;

    protected string $remote_id = '';

    protected string $title = '';

    protected string $description = '';

    protected string $sanitation = '';

    protected string $other_service = '';

    protected string $traffic_infrastructure = '';

    protected string $payment_accepted = '';

    protected string $distance_to_public_transport = '';

    protected string $offers = '';

    protected int $spaces_car = 0;

    protected int $spaces_handicapped = 0;

    protected int $spaces_bus = 0;

    protected int $spaces_ev_charging = 0;

    protected int $spaces_motorcycle = 0;

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->parseAs($this->getRemoteId($node), $node, $language, $parserContext, $translationLanguages);
    }

    /**
     * A parking facility delivered inside its owner's node, manufactured by the owner.
     *
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function configureInline(
        array $node,
        string $language,
        ParserContext $parserContext,
        array $translationLanguages,
        string $parentRemoteId,
        int $ordinal
    ): void {
        $this->parseAs($parentRemoteId . self::INLINE_SEPARATOR . $ordinal, $node, $language, $parserContext, $translationLanguages);
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    protected function parseAs(string $remoteId, array $node, string $language, ParserContext $parserContext, array $translationLanguages): void
    {
        $this->remote_id = $remoteId;
        $this->parseSpaces($node, $parserContext);

        $localisedFields = [
            'title' => 'schema:name',
            'description' => 'schema:description',
        ];
        $concatenatedFields = [
            'sanitation' => 'thuecat:sanitation',
            'other_service' => 'thuecat:otherService',
            'traffic_infrastructure' => 'thuecat:trafficInfrastructure',
            'payment_accepted' => 'schema:paymentAccepted',
        ];
        foreach ($localisedFields as $field => $jsonldName) {
            $this->$field = $this->extractValue($node[$jsonldName] ?? null, $language);
        }

        foreach ($concatenatedFields as $field => $jsonldName) {
            $this->$field = $this->extractConcatenatedString($node[$jsonldName] ?? null, $language);
        }

        $this->distance_to_public_transport = $this->buildDistanceToPublicTransport(
            $node['thuecat:distanceToPublicTransport'] ?? null,
            $language
        );
        $this->offers = $this->buildOffers($node['schema:makesOffer'] ?? null, $language);

        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $value = $this->extractValue($node[$jsonldName] ?? null, $code);
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

        $this->buildOpeningHourSpecifications($node, $this->remote_id);

        $this->buildAddress($node, $this->remote_id, $language, $translationLanguages);

        $this->recordTransient('containedInPlace', $node['schema:containedInPlace'] ?? null);
        $this->recordTransient('managedBy', $node['thuecat:contentResponsible'] ?? null);

        $this->recordMediaTransient(
            $node['schema:photo'] ?? null,
            $node['schema:image'] ?? null,
            $node['schema:video'] ?? null,
        );
    }

    public function handlesTypes(): array
    {
        return ['schema:ParkingFacility'];
    }

    /**
     * An entry naming several vehicle types counts for each of them, so the columns are not summed.
     *
     * @param array<string, mixed> $node
     */
    protected function parseSpaces(array $node, ParserContext $parserContext): void
    {
        foreach ($this->nodesOf($node['thuecat:numberOfParkingSpace'] ?? null) as $entry) {
            $spaces = (int)$this->extractValue($entry['schema:value'] ?? null, '');
            foreach ($this->nodesOf($entry['schema:unitText'] ?? null) as $vehicleType) {
                $vehicle = $this->extractValue($vehicleType, '');
                $field = self::VEHICLE_TYPE_FIELDS[$vehicle] ?? null;
                if ($field === null) {
                    $this->reportUnmatched($parserContext, 'thuecat:numberOfParkingSpace', $vehicle);
                    continue;
                }

                $this->$field += $spaces;
            }
        }
    }
}
