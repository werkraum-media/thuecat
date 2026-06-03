<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Pagination;

use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class PaginationFactory
{
    private const DEFAULT_ITEMS_PER_PAGE = 20;

    /**
     * Build paginator + pagination for a query result, reading itemsPerPage
     * from plugin settings (falling back to the default).
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

        $paginator = new QueryResultPaginator($items, $currentPage, $itemsPerPage);

        return new PaginationResult($paginator, new SimplePagination($paginator), $itemsPerPage);
    }
}
