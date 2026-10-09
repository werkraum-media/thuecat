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

namespace WerkraumMedia\ThueCat\Pagination;

use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class PaginationFactory
{
    private const DEFAULT_ITEMS_PER_PAGE = 20;

    /**
     * Build paginator + pagination for a query result, reading itemsPerPage
     * from plugin settings (falling back to the site settings default).
     *
     * @param array<mixed> $settings
     */
    public function fromSettings(
        QueryResultInterface $items,
        int $currentPage,
        array $settings
    ): PaginationResult {
        $setting = $settings['itemsPerPage'] ?? null;
        $itemsPerPage = MathUtility::canBeInterpretedAsInteger($setting)
            ? (int)$setting
            : 0;
        if ($itemsPerPage < 1) {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        }

        return $this->withFixedItemsPerPage($items, $currentPage, $itemsPerPage);
    }

    /**
     * For callers without plugin settings, e.g. backend modules.
     */
    public function withFixedItemsPerPage(
        QueryResultInterface $items,
        int $currentPage,
        int $itemsPerPage
    ): PaginationResult {
        $paginator = new QueryResultPaginator($items, $currentPage, $itemsPerPage);

        return new PaginationResult($paginator, new SlidingWindowPagination($paginator, 6), $itemsPerPage);
    }
}
