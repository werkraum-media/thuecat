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

namespace WerkraumMedia\ThueCat\Service;

/**
 * Which select columns of a table relate to a ThueCat record and therefore
 * belong to one site's scope.
 *
 * The single criterion behind both backend surfaces: the TCA listener asks it
 * which columns to bound by site, and the page TSconfig provider asks it which
 * columns need an id list. A select field pointing at a site-scoped table is
 * scoped by both without being named anywhere, and the two cannot disagree
 * about which fields those are.
 *
 * What stays explicit is SCOPED_TABLES, the statement of what counts as a
 * ThueCat record. A new record kind raises the question of whether its table
 * belongs here — usually yes, but a table shared across sites on purpose, or
 * one never used as a relation target, is correctly left out. Nothing detects
 * an omission: a select field pointing at a missing table keeps working and
 * offers the whole installation. See Documentation/BackendRelationScoping.rst.
 */
class SiteScopedSelectFields
{
    /**
     * @var list<string>
     */
    protected const SCOPED_TABLES = [
        'tx_thuecat_town',
        'tx_thuecat_organisation',
        'tx_thuecat_tourist_attraction',
        'tx_thuecat_tourist_information',
        'tx_thuecat_parking_facility',
        'tx_thuecat_trail',
    ];

    /**
     * The clause both backend surfaces run, written into foreign_table_where by
     * the listeners and into TCEFORM.suggest.default.addWhere for the wizard.
     * The marker's value comes from page TSconfig.
     */
    public function scopeClause(string $foreignTable): string
    {
        return implode(' ', $this->scopeConditions($foreignTable));
    }

    /**
     * @return list<string>
     */
    public function scopeConditions(string $foreignTable): array
    {
        return [
            'AND {#' . $foreignTable . '}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)',
            'AND {#' . $foreignTable . '}.{#sys_language_uid} IN (0, -1)',
        ];
    }

    /**
     * Adds each scope condition a matching column does not already state,
     * leaving whatever else its foreign_table_where holds untouched.
     *
     * @param array<string, mixed> $tableConfiguration
     *
     * @return array<string, mixed>
     */
    public function withScopeClause(string $table, array $tableConfiguration): array
    {
        $columns = $tableConfiguration['columns'] ?? null;
        if (!is_array($columns)) {
            return $tableConfiguration;
        }

        foreach ($this->matchingColumns($table, $tableConfiguration) as $columnName => $foreignTable) {
            $column = $columns[$columnName];
            if (!is_array($column) || !is_array($column['config'] ?? null)) {
                continue;
            }

            $existing = $column['config']['foreign_table_where'] ?? '';
            $existing = is_string($existing) ? $existing : '';

            $missing = [];
            foreach ($this->scopeConditions($foreignTable) as $condition) {
                if (!$this->alreadyStates($existing, $condition)) {
                    $missing[] = $condition;
                }
            }

            if ($missing === []) {
                continue;
            }

            $column['config']['foreign_table_where'] = trim($existing . ' ' . implode(' ', $missing));
            $columns[$columnName] = $column;
        }

        $tableConfiguration['columns'] = $columns;

        return $tableConfiguration;
    }

    /**
     * Compare existing clauses, so only lacking ones are added
     */
    protected function alreadyStates(string $clause, string $condition): bool
    {
        $normalise = static fn (string $sql): string => (string)preg_replace('/\s+/', '', $sql);

        return str_contains($normalise($clause), $normalise($condition));
    }

    /**
     * @param array<string, mixed> $tableConfiguration
     *
     * @return array<string, string> column name => the table it relates to
     */
    public function matchingColumns(string $table, array $tableConfiguration): array
    {
        $columns = $tableConfiguration['columns'] ?? [];
        if (!is_array($columns)) {
            return [];
        }

        $ctrl = $tableConfiguration['ctrl'] ?? null;
        $translationParent = is_array($ctrl) ? ($ctrl['transOrigPointerField'] ?? '') : '';

        $matches = [];
        foreach ($columns as $columnName => $column) {
            if ($columnName === $translationParent) {
                continue;
            }

            $foreignTable = $this->scopedForeignTable($column);
            if ($foreignTable !== null) {
                $matches[(string)$columnName] = $foreignTable;
            }
        }

        return $matches;
    }

    /**
     * @param mixed $column
     */
    protected function scopedForeignTable($column): ?string
    {
        if (!is_array($column) || !is_array($column['config'] ?? null)) {
            return null;
        }

        $config = $column['config'];
        if (($config['type'] ?? '') !== 'select') {
            return null;
        }

        $foreignTable = $config['foreign_table'] ?? null;
        if (!is_string($foreignTable) || !in_array($foreignTable, static::SCOPED_TABLES, true)) {
            return null;
        }

        return $foreignTable;
    }
}
