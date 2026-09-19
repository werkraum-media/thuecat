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

namespace WerkraumMedia\ThueCat\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;

class SiteScopedSelectFieldsTest extends TestCase
{
    private SiteScopedSelectFields $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SiteScopedSelectFields();
    }

    #[Test]
    public function selectColumnPointingAtSiteScopedTableIsMatched(): void
    {
        $tableConfiguration = $this->tableConfiguration([
            'town' => $this->selectColumn('tx_thuecat_town'),
        ]);

        self::assertSame(
            ['town' => 'tx_thuecat_town'],
            $this->subject->matchingColumns('tx_thuecat_tourist_attraction', $tableConfiguration)
        );
    }

    #[Test]
    public function selectColumnPointingOutsideTheSiteScopedTablesIsNotMatched(): void
    {
        // Import log is not a record kind editors scope to a site.
        $tableConfiguration = $this->tableConfiguration([
            'log' => $this->selectColumn('tx_thuecat_import_log'),
        ]);

        self::assertSame(
            [],
            $this->subject->matchingColumns('tx_thuecat_import_log_entry', $tableConfiguration)
        );
    }

    #[Test]
    public function columnOfAnotherTypeIsNotMatched(): void
    {
        // Inline resolves through a pipeline foreign_table_where does not reach.
        $tableConfiguration = $this->tableConfiguration([
            'tourist_information' => [
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_tourist_information',
                ],
            ],
        ]);

        self::assertSame(
            [],
            $this->subject->matchingColumns('tx_thuecat_town', $tableConfiguration)
        );
    }

    #[Test]
    public function categoryColumnIsNotMatched(): void
    {
        // Already site-scoped via treeConfig.startingPoints.
        $tableConfiguration = $this->tableConfiguration([
            'categories' => [
                'config' => [
                    'type' => 'category',
                    'foreign_table' => 'sys_category',
                ],
            ],
        ]);

        self::assertSame(
            [],
            $this->subject->matchingColumns('tx_thuecat_tourist_attraction', $tableConfiguration)
        );
    }

    #[Test]
    public function translationParentIsNotMatchedAlthoughItPointsAtASiteScopedTable(): void
    {
        // Core-managed, and its foreign_table is the table itself.
        $tableConfiguration = $this->tableConfiguration(
            [
                'l10n_parent' => $this->selectColumn('tx_thuecat_town'),
            ],
            'l10n_parent'
        );

        self::assertSame(
            [],
            $this->subject->matchingColumns('tx_thuecat_town', $tableConfiguration)
        );
    }

    #[Test]
    public function translationParentIsReadFromCtrlRatherThanAssumed(): void
    {
        // A hard-coded name matches nothing on the other half, silently.
        $tableConfiguration = $this->tableConfiguration(
            [
                'l18n_parent' => $this->selectColumn('tx_thuecat_trail'),
                'contained_in_trail' => $this->selectColumn('tx_thuecat_trail'),
            ],
            'l18n_parent'
        );

        self::assertSame(
            ['contained_in_trail' => 'tx_thuecat_trail'],
            $this->subject->matchingColumns('tx_thuecat_trail', $tableConfiguration)
        );
    }

    #[Test]
    public function everyRelationFieldOfTheCurrentInventoryIsMatched(): void
    {
        $tableConfiguration = $this->tableConfiguration(
            [
                'l18n_parent' => $this->selectColumn('tx_thuecat_tourist_attraction'),
                'town' => $this->selectColumn('tx_thuecat_town'),
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
                'contained_in_organisation' => $this->selectColumn('tx_thuecat_organisation'),
                'contained_in_attraction' => $this->selectColumn('tx_thuecat_tourist_attraction'),
                'contained_in_tourist_information' => $this->selectColumn('tx_thuecat_tourist_information'),
                'contained_in_parking_facility' => $this->selectColumn('tx_thuecat_parking_facility'),
                'contained_in_trail' => $this->selectColumn('tx_thuecat_trail'),
            ],
            'l18n_parent'
        );

        self::assertSame(
            [
                'town' => 'tx_thuecat_town',
                'managed_by' => 'tx_thuecat_organisation',
                'contained_in_organisation' => 'tx_thuecat_organisation',
                'contained_in_attraction' => 'tx_thuecat_tourist_attraction',
                'contained_in_tourist_information' => 'tx_thuecat_tourist_information',
                'contained_in_parking_facility' => 'tx_thuecat_parking_facility',
                'contained_in_trail' => 'tx_thuecat_trail',
            ],
            $this->subject->matchingColumns('tx_thuecat_tourist_attraction', $tableConfiguration)
        );
    }

    #[Test]
    public function selectColumnWithoutForeignTableIsNotMatched(): void
    {
        $tableConfiguration = $this->tableConfiguration([
            'type' => [
                'config' => [
                    'type' => 'select',
                    'items' => [
                        ['label' => 'Static', 'value' => 'static'],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            [],
            $this->subject->matchingColumns('tx_thuecat_import_configuration', $tableConfiguration)
        );
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     *
     * @return array<string, mixed>
     */
    private function tableConfiguration(array $columns, string $transOrigPointerField = 'l10n_parent'): array
    {
        return [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => $transOrigPointerField,
            ],
            'columns' => $columns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectColumn(string $foreignTable): array
    {
        return [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => $foreignTable,
            ],
        ];
    }
}
