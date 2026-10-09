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

use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

class PaginationResult
{
    public function __construct(
        protected readonly QueryResultPaginator $paginator,
        protected readonly PaginationInterface $pagination,
        protected readonly int $itemsPerPage,
    ) {
    }

    public function getPaginator(): QueryResultPaginator
    {
        return $this->paginator;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }

    /**
     * @return iterable<mixed>
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginator->getPaginatedItems();
    }
}
