<?php

declare(strict_types=1);

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
