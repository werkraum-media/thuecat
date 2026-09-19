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
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;

/**
 * Applies the site scope clause to relation selects inside flexform sheets,
 * so a content element offers the records of the site its page belongs to.
 *
 * The content elements carrying such fields ship in a v14-only package; under
 * v13 there is nothing to match.
 */
#[AsEventListener]
final readonly class SiteScopedRelationsFlexFormListener
{
    public function __construct(
        private SiteScopedSelectFields $scopedFields,
    ) {
    }

    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $dataStructure = $event->getDataStructure();
        if (!is_array($dataStructure['sheets'] ?? null)) {
            return;
        }

        $table = is_string($event->getIdentifier()['tableName'] ?? null)
            ? $event->getIdentifier()['tableName']
            : '';

        $sheets = $dataStructure['sheets'];
        foreach ($sheets as $sheetName => $sheet) {
            if (!is_array($sheet) || !is_array($sheet['ROOT'] ?? null)) {
                continue;
            }

            $root = $sheet['ROOT'];
            if (!is_array($root['el'] ?? null)) {
                continue;
            }

            // A sheet has no ctrl, so no field is a translation parent; the
            // criterion reads the columns out of the same shape regardless.
            $scoped = $this->scopedFields->withScopeClause($table, ['columns' => $root['el']]);

            $root['el'] = $scoped['columns'];
            $sheet['ROOT'] = $root;
            $sheets[$sheetName] = $sheet;
        }

        $dataStructure['sheets'] = $sheets;

        $event->setDataStructure($dataStructure);
    }
}
