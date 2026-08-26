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

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * A time-bound notice about the state of a trail
 */
class TrailConditionEntity extends AbstractEntity
{
    public const TABLE = 'tx_thuecat_trail_condition';

    public const SEPARATOR = '::cond::';

    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';
    protected ?string $valid_from = null;
    protected string $latitude = '';
    protected string $longitude = '';

    /**
     * @param array<string, mixed> $node a single thuecat:trailCurrentConditions entry
     */
    public function configure(array $node, string $language, string $parentRemoteId = '', int $ordinal = 0): void
    {
        // The entry's own @id is a blank node, so position is the only
        // identity that survives a re-import.
        $this->remote_id = $parentRemoteId . self::SEPARATOR . $ordinal;
        $this->title = $this->extractValue($node['rdfs:label'] ?? null, $language);
        $this->description = $this->extractValue($node['schema:description'] ?? null, $language);

        $validFrom = $this->extractValue($node['schema:validFrom'] ?? null, $language);
        $this->valid_from = $validFrom === '' ? null : $validFrom;

        $this->extractGeo($node['schema:geo'] ?? null, $language);
    }

    public function configureTranslation(array $node, string $language, int $sysLanguageUid): void
    {
        $fields = [
            'title' => 'rdfs:label',
            'description' => 'schema:description',
        ];

        foreach ($fields as $field => $jsonldName) {
            $this->recordTranslation(
                $field,
                $this->extractValue($node[$jsonldName] ?? null, $language),
                $sysLanguageUid
            );
        }
    }

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
    }

    public function handlesTypes(): array
    {
        return [];
    }

    /** A partial pair is dropped — half a coordinate places nothing. */
    protected function extractGeo(mixed $geoNode, string $language): void
    {
        if (!is_array($geoNode)) {
            return;
        }

        $latitude = $this->extractValue($geoNode['schema:latitude'] ?? null, $language);
        $longitude = $this->extractValue($geoNode['schema:longitude'] ?? null, $language);

        if ($latitude === '' || $longitude === '') {
            return;
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
}
