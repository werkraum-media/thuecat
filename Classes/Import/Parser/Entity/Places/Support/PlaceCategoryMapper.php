<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Places\Support;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Category\SysCategoryMapper;

// Sparse EXAMPLE map for POI @type URIs; unmapped non-ignored types surface as
// "unmatched" in the import report, the signal editorial grows the map from.
class PlaceCategoryMapper extends SysCategoryMapper
{
    public function kind(): string
    {
        return 'categories';
    }

    public function sourcePrefix(): string
    {
        return 'type:';
    }

    protected function titleMap(): array
    {
        return [
            'schema:Museum' => 'Museum',
            'thuecat:CultureHistoricalMuseum' => 'Kulturhistorisches Museum',
            'schema:Bridge' => 'Brücke',
            'schema:Synagogue' => 'Synagoge',
            'thuecat:Cathedral' => 'Kathedrale',
            'thuecat:TechnicalMonument' => 'Technisches Denkmal',
            'schema:ParkingFacility' => 'Parkplatz',
        ];
    }

    protected function ignoredValues(): array
    {
        return [
            'schema:Thing',
            'schema:Place',
            'schema:TouristAttraction',
            'ttgds:PointOfInterest',
            'schema:CivicStructure',
            'ttgds:OtherInfraStructure',
            'thuecat:OtherPOI',
        ];
    }
}
