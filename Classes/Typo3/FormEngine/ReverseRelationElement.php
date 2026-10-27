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

namespace WerkraumMedia\ThueCat\Typo3\FormEngine;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Lists, read-only, the records of another table whose relation field points at the edited record.
 *
 * It is the reverse view of a select relation. As a field of type `none` it is never written,
 * copied, localized or language-aligned by DataHandler, unlike an `inline` field, which makes the
 * listed records children of the edited one.
 *
 * Records are listed in the edited record's language. A translation may point at the default
 * record or at its translation, so both are matched.
 *
 * Configured on the field:
 *
 *     'config' => [
 *         'type' => 'none',
 *         'renderType' => 'thuecatReverseRelation',
 *         'reverseRelation' => ['table' => 'tx_thuecat_town', 'field' => 'managed_by'],
 *     ],
 */
#[Autoconfigure(public: true)]
class ReverseRelationElement extends AbstractFormElement
{
    public const RENDER_TYPE = 'thuecatReverseRelation';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $relation = $this->reverseRelation();
        $table = is_string($relation['table'] ?? null) ? $relation['table'] : '';
        $field = is_string($relation['field'] ?? null) ? $relation['field'] : '';

        $titles = $table !== '' && $field !== '' ? $this->fetchTitles($table, $field) : [];

        $items = array_map(
            static fn (string $title): string => '<li class="list-group-item">' . htmlspecialchars($title) . '</li>',
            $titles
        );
        if ($items === []) {
            $items[] = '<li class="list-group-item">'
                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:reverse_relation.empty'))
                . '</li>';
        }

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(
            '<div class="formengine-field-item t3js-formengine-field-item">'
            . '<ul class="list-group">' . implode('', $items) . '</ul>'
            . '</div>'
        );

        return $resultArray;
    }

    /**
     * @return list<string>
     */
    protected function fetchTitles(string $table, string $field): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $labelField = $schema->getRawConfiguration()['label'] ?? null;
        $labelField = is_string($labelField) ? $labelField : 'uid';
        $language = $this->editedRecordLanguage();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder->select('uid', $labelField)
            ->from($table)
            ->where($queryBuilder->expr()->in(
                $field,
                $queryBuilder->createNamedParameter($this->editedRecordUids(), Connection::PARAM_INT_ARRAY)
            ))
            ->orderBy($labelField)
        ;

        if ($schema->isLanguageAware()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                $queryBuilder->createNamedParameter([$language, -1], Connection::PARAM_INT_ARRAY)
            ));
        }

        $titles = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $uid = $row['uid'] ?? 0;
            $label = $row[$labelField] ?? '';
            $titles[] = '[' . (is_numeric($uid) ? (int)$uid : 0) . '] ' . (is_scalar($label) ? (string)$label : '');
        }

        return $titles;
    }

    /**
     * The edited record and, for a translation, its default-language record.
     *
     * @return list<int>
     */
    protected function editedRecordUids(): array
    {
        $databaseRow = $this->databaseRow();
        $uids = [$this->firstValue($databaseRow['uid'] ?? 0)];

        $schema = $this->tcaSchemaFactory->get($this->tableName());
        if ($schema->isLanguageAware()) {
            $parentField = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
            $parent = $this->firstValue($databaseRow[$parentField] ?? 0);
            if ($parent > 0) {
                $uids[] = $parent;
            }
        }

        return $uids;
    }

    protected function editedRecordLanguage(): int
    {
        $schema = $this->tcaSchemaFactory->get($this->tableName());
        if (!$schema->isLanguageAware()) {
            return 0;
        }

        $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        return $this->firstValue($this->databaseRow()[$languageField] ?? 0);
    }

    /**
     * @return array<mixed>
     */
    protected function reverseRelation(): array
    {
        $parameterArray = is_array($this->data['parameterArray'] ?? null) ? $this->data['parameterArray'] : [];
        $fieldConf = is_array($parameterArray['fieldConf'] ?? null) ? $parameterArray['fieldConf'] : [];
        $config = is_array($fieldConf['config'] ?? null) ? $fieldConf['config'] : [];

        return is_array($config['reverseRelation'] ?? null) ? $config['reverseRelation'] : [];
    }

    /**
     * @return array<mixed>
     */
    protected function databaseRow(): array
    {
        return is_array($this->data['databaseRow'] ?? null) ? $this->data['databaseRow'] : [];
    }

    protected function tableName(): string
    {
        return is_string($this->data['tableName'] ?? null) ? $this->data['tableName'] : '';
    }

    // Form data providers turn relation values into lists of uids.
    protected function firstValue(mixed $value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_numeric($value) ? (int)$value : 0;
    }
}
