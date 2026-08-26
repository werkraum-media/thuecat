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
 * Where a trail begins or ends
 */
class TrailLocationEntity extends AbstractEntity
{
    public const TABLE = 'tx_thuecat_trail_location';

    public const SEPARATOR = '::loc::';

    public const TYPE_START = 'start';

    public const TYPE_END = 'end';

    protected string $remote_id = '';
    protected string $location_type = self::TYPE_START;
    protected string $title = '';
    protected string $latitude = '';
    protected string $longitude = '';

    /**
     * @param array<string, mixed> $node a thuecat:startLocation or thuecat:endLocation node
     */
    public function configure(array $node, string $language, string $parentRemoteId = '', string $locationType = self::TYPE_START): void
    {
        $this->remote_id = $parentRemoteId . self::SEPARATOR . $locationType;
        $this->location_type = $locationType;
        $this->title = $this->extractValue($node['rdfs:label'] ?? null, $language);

        $this->extractGeo($node['schema:geo'] ?? null, $language);
    }

    public function configureTranslation(array $node, string $language, int $sysLanguageUid): void
    {
        $this->recordTranslation(
            'title',
            $this->extractValue($node['rdfs:label'] ?? null, $language),
            $sysLanguageUid
        );
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
