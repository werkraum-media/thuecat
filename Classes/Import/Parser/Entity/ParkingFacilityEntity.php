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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class ParkingFacilityEntity extends AbstractPlaceEntity
{
    public const TABLE = 'tx_thuecat_parking_facility';

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
}
