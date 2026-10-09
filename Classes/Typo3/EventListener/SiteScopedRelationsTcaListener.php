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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;

/**
 * Bounds every relation select column to the pages of the site its record is
 * edited in, by appending a scope clause to the column's foreign_table_where.
 *
 * The clause carries ###PAGE_TSCONFIG_IDLIST###, whose value the page TSconfig
 * provider emits per request from the same criterion this listener uses. The
 * columns are derived rather than listed, so a relation field added later is
 * scoped without being registered anywhere.
 */
#[AsEventListener]
final readonly class SiteScopedRelationsTcaListener
{
    public function __construct(
        private SiteScopedSelectFields $scopedFields,
    ) {
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();

        foreach ($tca as $table => $tableConfiguration) {
            if (!is_string($table) || !is_array($tableConfiguration)) {
                continue;
            }

            /** @var array<string, mixed> $tableConfiguration */
            $tca[$table] = $this->scopedFields->withScopeClause($table, $tableConfiguration);
        }

        $event->setTca($tca);
    }
}
