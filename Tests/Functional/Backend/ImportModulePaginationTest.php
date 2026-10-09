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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Pagination\PaginationFactory;
use WerkraumMedia\ThueCat\Pagination\PaginationResult;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Rendering every log ever written exhausts memory.
 */
final class ImportModulePaginationTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsOnlyOnePageOfLogs(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportLogsForPagination.php');

        self::assertSame([12, 11, 10, 9, 8], $this->uidsOnPage(1));
    }

    #[Test]
    public function returnsRemainderOnLastPage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportLogsForPagination.php');

        self::assertSame([2, 1], $this->uidsOnPage(3));
    }

    #[Test]
    public function pagesOverAllLogs(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportLogsForPagination.php');

        $pagination = $this->paginate(1)->getPagination();

        self::assertSame(3, $pagination->getLastPageNumber());
    }

    /**
     * @return list<int>
     */
    private function uidsOnPage(int $page): array
    {
        $uids = [];

        foreach ($this->paginate($page)->getPaginatedItems() as $log) {
            self::assertInstanceOf(ImportLog::class, $log);
            $uids[] = (int)$log->getUid();
        }

        return $uids;
    }

    private function paginate(int $page): PaginationResult
    {
        return $this->get(PaginationFactory::class)->withFixedItemsPerPage(
            $this->get(ImportLogRepository::class)->findAll(),
            $page,
            5
        );
    }
}
