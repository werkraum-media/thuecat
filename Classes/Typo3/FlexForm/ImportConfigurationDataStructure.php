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

namespace WerkraumMedia\ThueCat\Typo3\FlexForm;

use WerkraumMedia\ThueCat\Extension;

// Composes the import-configuration FlexForm data structures in PHP so the
// fields shared across the three types (storagePid, category anchors, file
// folder, api key) are defined once. XML has no include mechanism.
class ImportConfigurationDataStructure
{
    private const LLL = 'LLL:EXT:' . Extension::EXTENSION_KEY . '/Resources/Private/Language/locallang_flexform.xlf:';

    /**
     * @return array<string, mixed>|null Null when the type is unknown.
     */
    public function forType(string $type): ?array
    {
        $fields = match ($type) {
            'default', 'static' => $this->staticFields(),
            'syncScope' => $this->syncScopeFields(),
            'containsPlace' => $this->containsPlaceFields(),
            default => null,
        };
        if ($fields === null) {
            return null;
        }

        return [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'sheetTitle' => self::LLL . 'importConfiguration.' . $type . '.sheetTitle',
                        'type' => 'array',
                        'el' => $fields,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staticFields(): array
    {
        return [
            'storagePid' => $this->storagePidField('static'),
            'categoryStoragePid' => $this->categoryStoragePidField(),
            'categoryParent' => $this->categoryParentField(),
            'fileFolder' => $this->fileFolderField('static'),
            'importTarget' => $this->importTargetField(),
            'apiDomain' => $this->apiDomainField(),
            'urls' => $this->urlsSection(),
            'apiKey' => $this->apiKeyField(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncScopeFields(): array
    {
        return [
            'storagePid' => $this->storagePidField('syncScope'),
            'categoryStoragePid' => $this->categoryStoragePidField(),
            'categoryParent' => $this->categoryParentField(),
            'fileFolder' => $this->fileFolderField('syncScope'),
            'importTarget' => $this->importTargetField(),
            'apiDomain' => $this->apiDomainField(),
            'syncScopeId' => [
                'label' => self::LLL . 'importConfiguration.syncScope.syncScopeId',
                'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
            ],
            'apiKey' => $this->apiKeyField(),
            'fetchLastXDays' => [
                'label' => self::LLL . 'importConfiguration.syncScope.fetchLastXDays',
                'description' => self::LLL . 'importConfiguration.syncScope.fetchLastXDays.description',
                'config' => ['type' => 'number'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function containsPlaceFields(): array
    {
        return [
            'storagePid' => $this->storagePidField('containsPlace'),
            'categoryStoragePid' => $this->categoryStoragePidField(),
            'categoryParent' => $this->categoryParentField(),
            'fileFolder' => $this->fileFolderField('containsPlace'),
            'importTarget' => $this->importTargetField(),
            'apiDomain' => $this->apiDomainField(),
            'containsPlaceId' => [
                'label' => self::LLL . 'importConfiguration.containsPlace.containsPlaceId',
                'description' => self::LLL . 'importConfiguration.containsPlace.containsPlaceId.description',
                'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
            ],
            'apiKey' => $this->apiKeyField(),
        ];
    }

    /**
     * Required page selector; a change reloads the form so the category anchors
     * reveal and (later) scope their choices. onChange=reload works on a group
     * field in v14 (wired generically in SingleFieldContainer).
     *
     * @return array<string, mixed>
     */
    private function storagePidField(string $type): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.' . $type . '.storagePid',
            'onChange' => 'reload',
            'config' => $this->pageSelectField() + ['required' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryStoragePidField(): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.categoryStoragePid',
            'displayCond' => 'FIELD:storagePid:>:0',
            'config' => $this->pageSelectField(),
        ];
    }

    /**
     * Single-page picker via the element-browser wizard. Stores one uid (the
     * model reads one).
     *
     * @return array<string, mixed>
     */
    private function pageSelectField(): array
    {
        return [
            'type' => 'group',
            'allowed' => 'pages',
            'maxitems' => 1,
            'size' => 1,
            'suggestOptions' => [
                'default' => [
                    'addWhere' => 'AND pages.doktype = 254',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryParentField(): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.categoryParent',
            'displayCond' => 'FIELD:storagePid:>:0',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_category',
                'maxitems' => 1,
                'size' => 1,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileFolderField(string $type): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.' . $type . '.fileFolder',
            'description' => self::LLL . 'importConfiguration.' . $type . '.fileFolder.description',
            'config' => ['type' => 'folder', 'maxitems' => 1, 'minitems' => 1],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiKeyField(): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.apiKey',
            'description' => self::LLL . 'importConfiguration.apiKey.description',
            'config' => ['type' => 'input', 'nullable' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importTargetField(): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.syncScope.importTarget',
            'description' => self::LLL . 'importConfiguration.syncScope.importTarget.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => self::LLL . 'importConfiguration.syncScope.importTarget.thuecat', 'value' => 'thuecat'],
                    ['label' => self::LLL . 'importConfiguration.syncScope.importTarget.events', 'value' => 'events'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiDomainField(): array
    {
        return [
            'label' => self::LLL . 'importConfiguration.syncScope.apiDomain',
            'description' => self::LLL . 'importConfiguration.syncScope.apiDomain.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'default' => 'https://cdb.thuecat.org'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function urlsSection(): array
    {
        return [
            'title' => self::LLL . 'importConfiguration.static.urls',
            // Must be the string '1'; core compares strictly (=== '1').
            'section' => '1',
            'type' => 'array',
            'el' => [
                'url' => [
                    'title' => self::LLL . 'importConfiguration.static.url',
                    'type' => 'array',
                    'el' => [
                        'title' => [
                            'label' => self::LLL . 'importConfiguration.static.url.title.label',
                            'description' => self::LLL . 'importConfiguration.static.url.title.description',
                            'config' => ['type' => 'input', 'eval' => 'trim', 'readOnly' => true],
                        ],
                        'url' => [
                            'label' => self::LLL . 'importConfiguration.static.url',
                            'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
                        ],
                    ],
                ],
            ],
        ];
    }
}
