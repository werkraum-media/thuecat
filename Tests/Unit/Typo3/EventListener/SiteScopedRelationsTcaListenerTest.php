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
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;
use WerkraumMedia\ThueCat\Typo3\EventListener\SiteScopedRelationsTcaListener;

class SiteScopedRelationsTcaListenerTest extends TestCase
{
    private SiteScopedRelationsTcaListener $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SiteScopedRelationsTcaListener(new SiteScopedSelectFields());
    }

    #[Test]
    public function siteScopedSelectColumnGainsTheScopeClause(): void
    {
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn('tx_thuecat_town'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($tca), 'town')
        );
    }

    #[Test]
    public function columnOutsideTheCriterionIsLeftAlone(): void
    {
        $tca = $this->tcaWithColumns([
            'log' => $this->selectColumn('tx_thuecat_import_log'),
        ]);

        self::assertNull($this->clauseFor($this->process($tca), 'log'));
    }

    #[Test]
    public function existingClauseIsKeptAndTheScopeClauseAppended(): void
    {
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn('tx_thuecat_town', 'AND {#tx_thuecat_town}.{#title} != \'\''),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#title} != \'\''
            . ' AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($tca), 'town')
        );
    }

    #[Test]
    public function fieldAlreadyRestrictedToDefaultLanguageIsNotRestrictedTwice(): void
    {
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn(
                'tx_thuecat_town',
                'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)'
            ),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)'
            . ' AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)',
            $this->clauseFor($this->process($tca), 'town')
        );
    }

    #[Test]
    public function applyingTheListenerTwiceAppendsTheClauseOnce(): void
    {
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn('tx_thuecat_town'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($this->process($tca)), 'town')
        );
    }

    #[Test]
    public function clauseFilteringOnPidForAnotherReasonIsNotMistakenForOurs(): void
    {
        // Idempotence keys on the scope clause, not on any mention of pid.
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn('tx_thuecat_town', 'AND {#tx_thuecat_town}.{#pid} > 0'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#pid} > 0'
            . ' AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_town}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($tca), 'town')
        );
    }

    #[Test]
    public function whitespaceVariantOfAnExistingConditionCountsAsStated(): void
    {
        $tca = $this->tcaWithColumns([
            'town' => $this->selectColumn(
                'tx_thuecat_town',
                'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0,-1)'
            ),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_town}.{#sys_language_uid} IN (0,-1)'
            . ' AND {#tx_thuecat_town}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)',
            $this->clauseFor($this->process($tca), 'town')
        );
    }

    #[Test]
    public function translationParentIsLeftAlone(): void
    {
        $tca = $this->tcaWithColumns(
            [
                'l18n_parent' => $this->selectColumn('tx_thuecat_tourist_attraction'),
            ],
            'l18n_parent'
        );

        self::assertNull($this->clauseFor($this->process($tca), 'l18n_parent'));
    }

    #[Test]
    public function clauseNamesTheForeignTableRatherThanTheEditedTable(): void
    {
        // The condition filters the related records, which live in the foreign
        // table; naming the edited table would filter the wrong side.
        $tca = $this->tcaWithColumns([
            'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
        ]);

        self::assertSame(
            'AND {#tx_thuecat_organisation}.{#pid} IN (###PAGE_TSCONFIG_IDLIST###)'
            . ' AND {#tx_thuecat_organisation}.{#sys_language_uid} IN (0, -1)',
            $this->clauseFor($this->process($tca), 'managed_by')
        );
    }

    /**
     * @param array<string, mixed> $tca
     */
    private function clauseFor(array $tca, string $column): ?string
    {
        $node = $tca;
        foreach (['tx_thuecat_tourist_attraction', 'columns', $column, 'config', 'foreign_table_where'] as $key) {
            if (!is_array($node) || !isset($node[$key])) {
                return null;
            }
            $node = $node[$key];
        }

        return is_string($node) ? $node : null;
    }

    /**
     * @param array<string, mixed> $tca
     *
     * @return array<string, mixed>
     */
    private function process(array $tca): array
    {
        $event = new AfterTcaCompilationEvent($tca);
        ($this->subject)($event);

        $result = $event->getTca();

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     *
     * @return array<string, mixed>
     */
    private function tcaWithColumns(array $columns, string $transOrigPointerField = 'l18n_parent'): array
    {
        return [
            'tx_thuecat_tourist_attraction' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => $transOrigPointerField,
                ],
                'columns' => $columns,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectColumn(string $foreignTable, ?string $foreignTableWhere = null): array
    {
        $config = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => $foreignTable,
        ];

        if ($foreignTableWhere !== null) {
            $config['foreign_table_where'] = $foreignTableWhere;
        }

        return ['config' => $config];
    }
}
