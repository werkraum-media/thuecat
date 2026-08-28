<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

use WerkraumMedia\ThueCat\Domain\Model\TrailSeason;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * Entity to import entries that carry thuecat:trail
 */
class TrailEntity extends AbstractEntity
{
    public const TABLE = 'tx_thuecat_trail';

    /**
     * The `logo` kind is trail-specific; the resolver routes any kind through
     * this map, so no shared media handling knows about it.
     */
    public const MEDIA_FIELDS = [
        'photo' => 'main_image',
        'image' => 'media_files',
        'logo' => 'logo',
    ];

    // Closed set of values, derived from the schema.org ontology
    public const OPENING_STATUSES = [
        'Open',
        'Closed',
        'WeekendOnly',
        'NoInformation',
    ];

    // keys present in json entry map to dedicated fields
    private const OTHER_DESCRIPTIONS = [
        'short_description' => 'thuecat:shortDescription',
        'directions' => 'thuecat:directions',
        'getting_there' => 'thuecat:gettingThere',
        'parking' => 'thuecat:parking',
        'public_transit' => 'thuecat:publicTransit',
        'safety_guidelines' => 'thuecat:safetyGuidelines',
        'equipment' => 'thuecat:equipment',
        'additional_information' => 'thuecat:additionalInformation',
        'tip' => 'thuecat:tip',
    ];

    /** Rating column prefix => the name under thuecat:trailRatings. */
    private const RATINGS = [
        'rating_landscape' => 'thuecat:landscape',
        'rating_condition' => 'thuecat:ratingCondition',
        'rating_difficulty' => 'thuecat:ratingDifficulty',
        'rating_quality_of_experience' => 'thuecat:ratingQualityOfExperience',
        'rating_technique' => 'thuecat:ratingTechnique',
    ];

    protected int $priority = 20;
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    protected string $short_description = '';
    protected string $directions = '';
    protected string $getting_there = '';
    protected string $parking = '';
    protected string $public_transit = '';
    protected string $safety_guidelines = '';
    protected string $equipment = '';
    protected string $additional_information = '';
    protected string $tip = '';

    protected string $opening_status = '';
    protected int $season = 0;

    protected string $elevation_profile = '';
    protected string $elevation_profile_fall_back = '';

    protected string $route_line = '';
    protected string $gpx_url = '';

    protected string $distance = '';
    protected string $distance_unit = '';
    protected string $duration = '';
    protected string $duration_unit = '';
    protected string $exercise_type = '';
    protected string $min_altitude = '';
    protected string $max_altitude = '';
    protected string $ascent_elevation = '';
    protected string $descent_elevation = '';

    protected string $rating_landscape = '';
    protected string $rating_landscape_min = '';
    protected string $rating_landscape_max = '';
    protected string $rating_condition = '';
    protected string $rating_condition_min = '';
    protected string $rating_condition_max = '';
    protected string $rating_difficulty = '';
    protected string $rating_difficulty_min = '';
    protected string $rating_difficulty_max = '';
    protected string $rating_quality_of_experience = '';
    protected string $rating_quality_of_experience_min = '';
    protected string $rating_quality_of_experience_max = '';
    protected string $rating_technique = '';
    protected string $rating_technique_min = '';
    protected string $rating_technique_max = '';

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

        $descriptions = $node['thuecat:trailOtherDescriptions'] ?? null;
        foreach (self::OTHER_DESCRIPTIONS as $field => $jsonldName) {
            $this->$field = $this->extractValue(
                is_array($descriptions) ? ($descriptions[$jsonldName] ?? null) : null,
                $language
            );
        }

        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $this->recordTranslation(
                    $field,
                    $this->extractValue($node[$jsonldName] ?? null, $code),
                    $sysLanguageUid
                );
            }
            foreach (self::OTHER_DESCRIPTIONS as $field => $jsonldName) {
                $this->recordTranslation(
                    $field,
                    $this->extractValue(
                        is_array($descriptions) ? ($descriptions[$jsonldName] ?? null) : null,
                        $code
                    ),
                    $sysLanguageUid
                );
            }
        }

        $this->parseOpeningStatus($node, $language);
        $this->parseSeasons($node, $language);

        $this->elevation_profile = $this->extractValue($node['thuecat:elevationProfile'] ?? null, $language);
        $this->elevation_profile_fall_back = $this->extractValue(
            $node['thuecat:elevationProfileFallBack'] ?? null,
            $language
        );

        $this->parseRoute($node, $language);
        $this->parseMetrics($node, $language);

        $this->buildWayTypes($node, $language, $translationLanguages);
        $this->buildConditions($node, $language, $translationLanguages);
        $this->buildLocations($node, $language, $translationLanguages);

        $this->recordMediaTransient(
            $node['schema:photo'] ?? null,
            $node['schema:image'] ?? null,
            null,
        );
        $this->recordLogoMedia($node['schema:logo'] ?? null);

        $this->recordTransient('managedBy', $node['thuecat:contentResponsible'] ?? null);
        $this->recordKeywords($node);
    }

    private function recordLogoMedia(mixed $logo): void
    {
        foreach ($this->splitMediaByShape($logo) as $logoNode) {
            $this->_inlineMedia[] = ['kind' => 'logo', 'node' => $logoNode];
        }

        $references = $this->collectIds($this->bareReferencesOnly($logo));
        if ($references === []) {
            return;
        }

        $entries = $this->transients['media'] ?? [];
        foreach ($references as $id) {
            $entries[] = ['kind' => 'logo', 'id' => $id];
        }
        $this->transients['media'] = $entries;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    private function buildWayTypes(array $node, string $language, array $translationLanguages): void
    {
        $wayType = $node['schema:wayType'] ?? null;
        if (!is_array($wayType)) {
            return;
        }

        $segments = $wayType['thuecat:wayTypeLegend'] ?? null;
        if (!is_array($segments)) {
            return;
        }
        $segments = array_is_list($segments) ? $segments : [$segments];

        $ordinal = 0;
        foreach ($segments as $segment) {
            if (!is_array($segment) || $segment === []) {
                continue;
            }
            /** @var array<string, mixed> $segment JSON-LD nodes are string-keyed. */
            $child = new TrailWayTypeEntity();
            $child->configure($segment, $language, $this->remote_id, $ordinal);

            foreach ($translationLanguages as $code => $sysLanguageUid) {
                $child->configureTranslation($segment, $code, $sysLanguageUid);
            }

            $this->children[] = $child;
            $ordinal++;
        }
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    private function buildConditions(array $node, string $language, array $translationLanguages): void
    {
        $conditions = $node['thuecat:trailCurrentConditions'] ?? null;
        if (!is_array($conditions)) {
            return;
        }
        $conditions = array_is_list($conditions) ? $conditions : [$conditions];

        $ordinal = 0;
        foreach ($conditions as $condition) {
            if (!is_array($condition) || $condition === []) {
                continue;
            }
            /** @var array<string, mixed> $condition JSON-LD nodes are string-keyed. */
            $child = new TrailConditionEntity();
            $child->configure($condition, $language, $this->remote_id, $ordinal);

            foreach ($translationLanguages as $code => $sysLanguageUid) {
                $child->configureTranslation($condition, $code, $sysLanguageUid);
            }

            $this->children[] = $child;
            $ordinal++;
        }
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    private function buildLocations(array $node, string $language, array $translationLanguages): void
    {
        $roles = [
            TrailLocationEntity::TYPE_START => 'thuecat:startLocation',
            TrailLocationEntity::TYPE_END => 'thuecat:endLocation',
        ];

        foreach ($roles as $locationType => $jsonldName) {
            $locationNode = $node[$jsonldName] ?? null;
            if (!is_array($locationNode) || $locationNode === []) {
                continue;
            }
            /** @var array<string, mixed> $locationNode JSON-LD nodes are string-keyed. */
            $child = new TrailLocationEntity();
            $child->configure($locationNode, $language, $this->remote_id, $locationType);

            foreach ($translationLanguages as $code => $sysLanguageUid) {
                $child->configureTranslation($locationNode, $code, $sysLanguageUid);
            }

            $this->children[] = $child;
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    private function parseOpeningStatus(array $node, string $language): void
    {
        $status = $this->stripNamespacePrefix(
            $this->extractValue($node['thuecat:openingStatus'] ?? null, $language)
        );
        if (!in_array($status, self::OPENING_STATUSES, true)) {
            return;
        }

        $this->opening_status = $status;
    }

    /**
     * Unknown members are skipped
     *
     * @param array<string, mixed> $node
     */
    private function parseSeasons(array $node, string $language): void
    {
        $bits = 0;
        foreach ($this->extractConcatenatedMembers($node['thuecat:season'] ?? null, $language) as $member) {
            $bits |= TrailSeason::tryFrom($member)?->bit() ?? 0;
        }

        // Upstream having no seasons must clear the column
        $this->season = $bits === 0 ? self::PARSED_EMPTY_INTEGER : $bits;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function parseRoute(array $node, string $language): void
    {
        $geo = $node['schema:geo'] ?? null;
        if (is_array($geo)) {
            $this->route_line = $this->extractValue($geo['schema:line'] ?? null, $language);
        }

        $subjectOf = $node['schema:subjectOf'] ?? null;
        if (is_array($subjectOf)) {
            $this->gpx_url = $this->extractValue($subjectOf['schema:url'] ?? null, $language);
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    private function parseMetrics(array $node, string $language): void
    {
        $action = $node['schema:potentialAction'] ?? null;
        if (!is_array($action)) {
            return;
        }

        [$this->distance, $this->distance_unit] = $this->quantitativeValue($action['schema:distance'] ?? null, $language);
        [$this->duration, $this->duration_unit] = $this->quantitativeValue($action['thuecat:time'] ?? null, $language);
        $this->exercise_type = $this->extractValue($action['schema:exerciseType'] ?? null, $language);

        $elevation = $action['thuecat:elevation'] ?? null;
        if (is_array($elevation)) {
            $altitudes = [
                'min_altitude' => 'thuecat:minAltitude',
                'max_altitude' => 'thuecat:maxAltitude',
                'ascent_elevation' => 'thuecat:ascentElevation',
                'descent_elevation' => 'thuecat:descentElevation',
            ];
            foreach ($altitudes as $field => $jsonldName) {
                [$this->$field] = $this->quantitativeValue($elevation[$jsonldName] ?? null, $language);
            }
        }

        $ratings = $action['thuecat:trailRatings'] ?? null;
        if (!is_array($ratings)) {
            return;
        }
        foreach (self::RATINGS as $field => $jsonldName) {
            $rating = $ratings[$jsonldName] ?? null;
            if (!is_array($rating)) {
                continue;
            }
            [$this->$field] = $this->quantitativeValue($rating, $language);
            // The scale travels with the value: a 1 means nothing without it.
            $this->{$field . '_min'} = $this->extractValue($rating['schema:minValue'] ?? null, $language);
            $this->{$field . '_max'} = $this->extractValue($rating['schema:maxValue'] ?? null, $language);
        }
    }

    /**
     * Value and unit of a schema:QuantitativeValue node. Kept as strings so
     * the source's precision survives; a decimal column would be rounded.
     *
     * @return array{string, string}
     */
    private function quantitativeValue(mixed $value, string $language): array
    {
        if (!is_array($value)) {
            return ['', ''];
        }

        return [
            $this->extractValue($value['schema:value'] ?? null, $language),
            $this->stripNamespacePrefix($this->extractValue($value['schema:unitCode'] ?? null, $language)),
        ];
    }

    public function handlesTypes(): array
    {
        return ['thuecat:Trail'];
    }
}
