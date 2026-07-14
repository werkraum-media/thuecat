<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Category;

/**
 * @extends Repository<Category>
 */
class CategoryRepository extends Repository
{
    /**
     * All categories whose parent is one of $parentUids, sorted by title.
     * Storage page restriction is dropped: sys_category rows live wherever the
     * import put them, not on the plugin's pages.
     *
     * @param int[] $parentUids
     *
     * @return Category[]
     */
    public function findByParents(array $parentUids): array
    {
        if ($parentUids === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('parent', $parentUids));
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute()->toArray();
    }
}
