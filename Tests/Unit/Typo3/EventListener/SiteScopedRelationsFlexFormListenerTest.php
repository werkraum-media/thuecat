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

namespace WerkraumMedia\ThueCat\Tests\Unit\Typo3\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;
use WerkraumMedia\ThueCat\Typo3\EventListener\SiteScopedRelationsFlexFormListener;

class SiteScopedRelationsFlexFormListenerTest extends TestCase
{
    private SiteScopedRelationsFlexFormListener $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SiteScopedRelationsFlexFormListener(new SiteScopedSelectFields());
    }

    #[Test]
    public function siteScopedSelectFieldGainsTheScopeClause(): void
    {
        $dataStructure = $this->dataStructureWithFields([
            'settings.towns' => $this->selectField('tx_thuecat_town'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($dataStructure), 'sDEF', 'settings.towns')
        );
    }

    #[Test]
    public function existingLanguageRestrictionIsNotAppliedTwice(): void
    {
        // The five thuecat_ces select fields ship exactly this clause. That is
        // a replica, not a reference: thuecat_ces cannot be loaded here, so
        // nothing asserts the two stay in step. Re-read their config.yaml when
        // this fixture is touched.
        $dataStructure = $this->dataStructureWithFields([
            'settings.towns' => $this->selectField(
                'tx_thuecat_town',
                'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)'
            ),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)'
            . ' AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)',
            $this->clauseFor($this->process($dataStructure), 'sDEF', 'settings.towns')
        );
    }

    #[Test]
    public function categoryTreeFieldIsLeftAlone(): void
    {
        $dataStructure = $this->dataStructureWithFields([
            'settings.categories' => [
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectTree',
                    'foreign_table' => 'sys_category',
                    'treeConfig' => [
                        'startingPoints' => '###SITE:settings.import.thuecat.category.parent###',
                    ],
                ],
            ],
        ]);

        self::assertNull(
            $this->clauseFor($this->process($dataStructure), 'sDEF', 'settings.categories')
        );
    }

    #[Test]
    public function fieldsAcrossSeveralSheetsAreReached(): void
    {
        $dataStructure = [
            'sheets' => [
                'sDEF' => $this->sheetWithFields([
                    'settings.towns' => $this->selectField('tx_thuecat_town'),
                ]),
                'extra' => $this->sheetWithFields([
                    'settings.selectedRecords' => $this->selectField('tx_thuecat_trail'),
                ]),
            ],
        ];

        $result = $this->process($dataStructure);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($result, 'sDEF', 'settings.towns')
        );
        self::assertSame(
            'AND {#tx_thuecat_trail}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_trail}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($result, 'extra', 'settings.selectedRecords')
        );
    }

    #[Test]
    public function applyingTheListenerTwiceAppendsTheClauseOnce(): void
    {
        $dataStructure = $this->dataStructureWithFields([
            'settings.towns' => $this->selectField('tx_thuecat_town'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($this->process($dataStructure)), 'sDEF', 'settings.towns')
        );
    }

    #[Test]
    public function structureWithoutSheetsIsHandled(): void
    {
        $result = $this->process(['ROOT' => []]);

        self::assertSame(['ROOT' => []], $result);
    }

    /**
     * @param array<string, mixed> $dataStructure
     */
    private function clauseFor(array $dataStructure, string $sheet, string $field): ?string
    {
        $node = $dataStructure;
        foreach (['sheets', $sheet, 'ROOT', 'el', $field, 'config', 'foreign_table_where'] as $key) {
            if (!is_array($node) || !isset($node[$key])) {
                return null;
            }
            $node = $node[$key];
        }

        return is_string($node) ? $node : null;
    }

    /**
     * @param array<string, mixed> $dataStructure
     *
     * @return array<string, mixed>
     */
    private function process(array $dataStructure): array
    {
        $event = new AfterFlexFormDataStructureParsedEvent($dataStructure, [
            'tableName' => 'tt_content',
            'fieldName' => 'pi_flexform',
        ]);
        ($this->subject)($event);

        $result = $event->getDataStructure();

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     *
     * @return array<string, mixed>
     */
    private function dataStructureWithFields(array $fields): array
    {
        return [
            'sheets' => [
                'sDEF' => $this->sheetWithFields($fields),
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     *
     * @return array<string, mixed>
     */
    private function sheetWithFields(array $fields): array
    {
        return [
            'ROOT' => [
                'type' => 'array',
                'el' => $fields,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectField(string $foreignTable, ?string $foreignTableWhere = null): array
    {
        $config = [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => $foreignTable,
        ];

        if ($foreignTableWhere !== null) {
            $config['foreign_table_where'] = $foreignTableWhere;
        }

        return ['config' => $config];
    }
}
