<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Backend;

use TYPO3\CMS\Core\Database\ConnectionPool;

final class FileRepository
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function getFileUidBasedOnEntityId(string $id): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');

        $queryBuilder->select('file');
        $queryBuilder->from('sys_file_metadata');
        $queryBuilder->where($queryBuilder->expr()->eq(
            'publisher',
            // TODO: Move to constant.
            $queryBuilder->createNamedParameter('thuecat.org'),
        ));
        $queryBuilder->andWhere($queryBuilder->expr()->eq(
            'source',
            $queryBuilder->createNamedParameter($id),
        ));

        $fileUid = $queryBuilder->executeQuery()->fetchOne();
        if (is_numeric($fileUid)) {
            return (int) $fileUid;
        }

        return null;
    }
}
