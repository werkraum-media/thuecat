<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// The coverage the derived registration actually reaches in the compiled TCA,
// as opposed to what a fixture says it would reach.
class SiteScopedRelationCoverageTest extends AbstractImportTestCase
{
    #[Test]
    public function everyRelationFieldOfEveryRecordKindIsScoped(): void
    {
        self::assertSame(
            [
                'tx_events_domain_model_event' => [
                    'location',
                    'organizer',
                ],
                'tx_events_domain_model_location' => [
                    'children',
                ],
                'tx_thuecat_parking_facility' => [
                    'contained_in_attraction',
                    'contained_in_organisation',
                    'contained_in_parking_facility',
                    'contained_in_tourist_information',
                    'contained_in_trail',
                    'managed_by',
                    'town',
                ],
                'tx_thuecat_tourist_attraction' => [
                    'contained_in_attraction',
                    'contained_in_organisation',
                    'contained_in_parking_facility',
                    'contained_in_tourist_information',
                    'contained_in_trail',
                    'managed_by',
                    'town',
                ],
                'tx_thuecat_tourist_information' => [
                    'contained_in_attraction',
                    'contained_in_organisation',
                    'contained_in_parking_facility',
                    'contained_in_tourist_information',
                    'contained_in_trail',
                    'managed_by',
                    'town',
                ],
                'tx_thuecat_town' => [
                    'managed_by',
                ],
                'tx_thuecat_trail' => [
                    'managed_by',
                ],
            ],
            $this->scopedColumnsPerTable()
        );
    }

    #[Test]
    public function translationParentsAreNotScoped(): void
    {
        $scoped = $this->scopedColumnsPerTable();

        foreach ($scoped as $table => $columns) {
            self::assertNotContains('l10n_parent', $columns, $table);
            self::assertNotContains('l18n_parent', $columns, $table);
        }
    }

    #[Test]
    public function importLogRelationsAreNotScoped(): void
    {
        $scoped = $this->scopedColumnsPerTable();

        self::assertArrayNotHasKey('tx_thuecat_import_log', $scoped);
        self::assertArrayNotHasKey('tx_thuecat_import_log_entry', $scoped);
        self::assertArrayNotHasKey('tx_thuecat_import_configuration', $scoped);
    }

    #[Test]
    public function categoryFieldsAreNotScoped(): void
    {
        foreach ($this->scopedColumnsPerTable() as $table => $columns) {
            self::assertNotContains('categories', $columns, $table);
            self::assertNotContains('keywords', $columns, $table);
        }
    }

    /**
     * Every column of the compiled TCA carrying the scope clause, so the
     * assertion reads the outcome rather than re-running the criterion.
     *
     * @return array<string, list<string>>
     */
    private function scopedColumnsPerTable(): array
    {
        $scoped = [];

        $tca = $GLOBALS['TCA'] ?? [];
        if (!is_array($tca)) {
            return [];
        }

        foreach ($tca as $table => $configuration) {
            if (!is_array($configuration) || !is_array($configuration['columns'] ?? null)) {
                continue;
            }

            foreach ($configuration['columns'] as $columnName => $column) {
                if (!is_array($column) || !is_array($column['config'] ?? null)) {
                    continue;
                }

                $clause = $column['config']['foreign_table_where'] ?? '';
                if (is_string($clause) && str_contains($clause, '###PAGE_TSCONFIG_IDLIST###')) {
                    $scoped[(string)$table][] = (string)$columnName;
                }
            }
        }

        foreach ($scoped as $table => $columns) {
            sort($columns);
            $scoped[$table] = $columns;
        }
        ksort($scoped);

        return $scoped;
    }
}
