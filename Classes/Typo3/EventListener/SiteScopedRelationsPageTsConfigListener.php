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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\AbstractInvalidDataStructureException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;
use WerkraumMedia\ThueCat\Service\SitePageIds;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;

/**
 * Supplies the page ids that the scope clause's ###PAGE_TSCONFIG_IDLIST###
 * marker resolves to, for every relation field the criterion matches.
 *
 * The marker is read per table and per field on both backend surfaces, and
 * core offers no wildcard for it, so one line is emitted per scoped field.
 * They are derived from the criterion rather than listed, so the set of fields
 * carrying the clause and the set receiving an id list cannot drift apart.
 */
#[AsEventListener]
final readonly class SiteScopedRelationsPageTsConfigListener
{
    public function __construct(
        private SitePageIds $sitePageIds,
        private SiteScopedSelectFields $scopedFields,
        private FlexFormTools $flexFormTools,
    ) {
    }

    public function __invoke(ModifyLoadedPageTsConfigEvent $event): void
    {
        $pageId = $this->currentPageId($event->getRootLine());
        if ($pageId === null) {
            return;
        }

        $pageIds = $this->sitePageIds->forStoragePidOrEmpty($pageId);
        // An empty set must still be emitted: without a value the marker stays
        // unresolved and the clause offers the whole table.
        $idList = $pageIds === [] ? '0' : implode(',', $pageIds);

        $lines = [];
        foreach ($this->scopedTca() as $table => $tableConfiguration) {
            foreach (array_keys($this->scopedFields->matchingColumns($table, $tableConfiguration)) as $column) {
                $lines[] = 'TCEFORM.' . $table . '.' . $column . '.PAGE_TSCONFIG_IDLIST = ' . $idList;
            }

            foreach ($this->flexFormPaths($table, $tableConfiguration) as $path) {
                $lines[] = 'TCEFORM.' . $path . '.PAGE_TSCONFIG_IDLIST = ' . $idList;
            }
        }

        if ($lines === []) {
            return;
        }

        // Appended under a numeric key, while every core collector uses string
        // keys, so the write-back in TsConfigTreeBuilder cannot clobber it.
        $event->addTsConfig(implode("\n", $lines));
    }

    /**
     * The TSconfig paths of scoped fields inside flexform sheets, as
     * `<table>.<flexField>.<dataStructureKey>.<sheet>.<field>`.
     *
     * TcaFlexProcess resolves the marker at that exact path, with no wildcard,
     * and the data structure key is the record type. The dot in a field name
     * like `settings.towns` is escaped, because TSconfig reads it as a path
     * separator otherwise.
     *
     * @param array<string, mixed> $tableConfiguration
     *
     * @return list<string>
     */
    private function flexFormPaths(string $table, array $tableConfiguration): array
    {
        $types = $tableConfiguration['types'] ?? null;
        if (!is_array($types)) {
            return [];
        }

        $flexFields = $this->flexFieldNames($tableConfiguration);
        if ($flexFields === []) {
            return [];
        }

        $paths = [];
        foreach ($types as $typeName => $typeConfiguration) {
            if (!is_array($typeConfiguration)) {
                continue;
            }

            $overrides = $typeConfiguration['columnsOverrides'] ?? null;
            if (!is_array($overrides)) {
                continue;
            }

            foreach ($flexFields as $flexField) {
                // The ds in TCA is an XML string, and for Content Blocks only a
                // placeholder; the real structure comes from parsing.
                $override = $overrides[$flexField] ?? null;
                if (!is_array($override) || !is_array($override['config'] ?? null) || !isset($override['config']['ds'])) {
                    continue;
                }

                foreach ($this->parsedSheets($table, $flexField, (string)$typeName) as $sheetName => $fields) {
                    $matching = $this->scopedFields->matchingColumns($table, ['columns' => $fields]);
                    foreach (array_keys($matching) as $field) {
                        $paths[] = $table
                            . '.' . $flexField
                            . '.' . $typeName
                            . '.' . $sheetName
                            . '.' . str_replace('.', '\\.', (string)$field);
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * The fields of each sheet, per sheet name. The identifier is built from
     * the record type rather than from a row, because TSconfig is assembled
     * without one; `dataStructureKey` is the only part a row would decide.
     *
     * @return array<string, array<string, mixed>>
     */
    private function parsedSheets(string $table, string $flexField, string $type): array
    {
        $identifier = json_encode([
            'type' => 'tca',
            'tableName' => $table,
            'fieldName' => $flexField,
            'dataStructureKey' => $type,
        ], JSON_THROW_ON_ERROR);

        $tca = $GLOBALS['TCA'] ?? null;
        $schema = is_array($tca) ? ($tca[$table] ?? null) : null;

        try {
            $dataStructure = $this->flexFormTools->parseDataStructureByIdentifier(
                $identifier,
                is_array($schema) ? $schema : null
            );
        } catch (AbstractInvalidDataStructureException) {
            // A type whose structure cannot be resolved simply has no scoped
            // fields; it must not break TSconfig assembly for every other page.
            return [];
        }

        $parsedSheets = $dataStructure['sheets'] ?? null;
        if (!is_array($parsedSheets)) {
            return [];
        }

        $sheets = [];
        foreach ($parsedSheets as $sheetName => $sheet) {
            if (!is_string($sheetName) || !is_array($sheet) || !is_array($sheet['ROOT'] ?? null)) {
                continue;
            }

            $fields = $sheet['ROOT']['el'] ?? null;
            if (!is_array($fields)) {
                continue;
            }

            $typedFields = [];
            foreach ($fields as $fieldName => $field) {
                $typedFields[(string)$fieldName] = $field;
            }

            $sheets[$sheetName] = $typedFields;
        }

        return $sheets;
    }

    /**
     * @param array<string, mixed> $tableConfiguration
     *
     * @return list<string>
     */
    private function flexFieldNames(array $tableConfiguration): array
    {
        $columns = $tableConfiguration['columns'] ?? null;
        if (!is_array($columns)) {
            return [];
        }

        $flexFields = [];
        foreach ($columns as $columnName => $column) {
            if (!is_array($column) || !is_array($column['config'] ?? null)) {
                continue;
            }

            if (($column['config']['type'] ?? '') === 'flex') {
                $flexFields[] = (string)$columnName;
            }
        }

        return $flexFields;
    }

    /**
     * The page whose TSconfig is being assembled: the deepest entry of the
     * rootline, which runs root first and may open with a synthetic uid 0.
     * Taking the first entry resolves to no site and scopes every field to
     * nothing.
     *
     * @param array<mixed> $rootLine
     */
    private function currentPageId(array $rootLine): ?int
    {
        foreach (array_reverse($rootLine) as $page) {
            if (!is_array($page)) {
                continue;
            }

            $uid = $page['uid'] ?? null;
            if (is_numeric($uid) && (int)$uid > 0) {
                return (int)$uid;
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function scopedTca(): array
    {
        $tca = $GLOBALS['TCA'] ?? [];
        if (!is_array($tca)) {
            return [];
        }

        $scoped = [];
        foreach ($tca as $table => $tableConfiguration) {
            if (is_string($table) && is_array($tableConfiguration)) {
                /** @var array<string, mixed> $tableConfiguration */
                $scoped[$table] = $tableConfiguration;
            }
        }

        return $scoped;
    }
}
