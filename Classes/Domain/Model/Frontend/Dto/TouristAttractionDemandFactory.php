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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class TouristAttractionDemandFactory
{
    /**
     * Single source of truth for editor settings → demand: each property the
     * editor configured in a list plugin is applied AND named, so the form can render it hidden.
     *
     * @param array<mixed> $settings
     */
    public function fromSettings(array $settings): EditorFilter
    {
        $demand = new TouristAttractionDemand();
        $locked = [];

        if (!empty($settings['towns']) && is_string($settings['towns'])) {
            $demand->setTowns(GeneralUtility::intExplode(',', $settings['towns'], true));
            $locked[] = 'towns';
        }
        if (!empty($settings['categories']) && is_string($settings['categories'])) {
            $demand->setCategories(GeneralUtility::intExplode(',', $settings['categories'], true));
            $locked[] = 'categories';
        }
        if (!empty($settings['keywords']) && is_string($settings['keywords'])) {
            $demand->setKeywords(GeneralUtility::intExplode(',', $settings['keywords'], true));
            $locked[] = 'keywords';
        }
        if (!empty($settings['petsAllowed'])) {
            $demand->setPetsAllowed(true);
            $locked[] = 'petsAllowed';
        }
        if (!empty($settings['isAccessibleForFree'])) {
            $demand->setIsAccessibleForFree(true);
            $locked[] = 'isAccessibleForFree';
        }
        if (!empty($settings['publicAccess'])) {
            $demand->setPublicAccess(true);
            $locked[] = 'publicAccess';
        }
        // Not locked: locked properties render as hidden form fields.
        if (is_string($settings['sortBy'] ?? null)) {
            $demand->setSortBy($settings['sortBy']);
        }

        return new EditorFilter($demand, $locked);
    }

    /**
     * Force the editor-locked values from $filter onto $demand so a visitor
     * search refines within the editor's set but can never widen past it.
     */
    public function applyEditorFilter(TouristAttractionDemand $demand, EditorFilter $filter): TouristAttractionDemand
    {
        $locked = $filter->getDemand();

        // @todo each new filter needs its own condition here
        if ($filter->isLocked('towns')) {
            $demand->setTowns($locked->getTowns());
        }
        if ($filter->isLocked('categories')) {
            $demand->setCategories($locked->getCategories());
        }
        if ($filter->isLocked('keywords')) {
            $demand->setKeywords($locked->getKeywords());
        }
        if ($filter->isLocked('petsAllowed')) {
            $demand->setPetsAllowed($locked->getPetsAllowed());
        }
        if ($filter->isLocked('isAccessibleForFree')) {
            $demand->setIsAccessibleForFree($locked->getIsAccessibleForFree());
        }
        if ($filter->isLocked('publicAccess')) {
            $demand->setPublicAccess($locked->getPublicAccess());
        }
        // Always the editor's, even when empty, so a request can never pick the order.
        $demand->setSortBy($locked->getSortBy());

        return $demand;
    }
}
