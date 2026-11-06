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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use ArrayAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

/**
 * An inline parking facility belongs to its venue alone, so the relations that pick parking facilities never offer
 * it. The fixture holds one top-level and one inline parking facility in the same storage folder.
 */
final class InlineParkingFacilitySelectionTest extends AbstractBackendTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Backend/InlineParkingFacility.csv');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function parkingFacilityRelations(): iterable
    {
        yield 'nearby' => ['parking_facility_near_by'];
        yield 'containing' => ['contained_in_parking_facility'];
    }

    #[Test]
    #[DataProvider('parkingFacilityRelations')]
    public function relationOffersNoInlineParkingFacility(string $field): void
    {
        $formData = $this->compileEditForm('tx_thuecat_tourist_attraction', 4900);
        $processedTca = is_array($formData['processedTca'] ?? null) ? $formData['processedTca'] : [];
        $columns = is_array($processedTca['columns'] ?? null) ? $processedTca['columns'] : [];
        $column = is_array($columns[$field] ?? null) ? $columns[$field] : [];
        $config = is_array($column['config'] ?? null) ? $column['config'] : [];
        $items = is_array($config['items'] ?? null) ? $config['items'] : [];

        $offered = [];
        foreach ($items as $item) {
            // SelectItem objects here, not arrays; both read through ArrayAccess.
            $value = (is_array($item) || $item instanceof ArrayAccess) ? ($item['value'] ?? null) : null;
            if (is_numeric($value) && (int)$value > 0) {
                $offered[] = (int)$value;
            }
        }
        $this->assertSame([4801], $offered);
    }
}
