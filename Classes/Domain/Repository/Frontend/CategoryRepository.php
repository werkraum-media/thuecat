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
     * All categories whose parent is one of $parents, sorted by title.
     * Storage page restriction is dropped: sys_category rows live wherever the
     * import put them, not on the plugin's pages.
     *
     * `parent` is an object relation, so it is compared against the categories
     * themselves; passing uids matches nothing.
     *
     * @param Category[] $parents
     *
     * @return Category[]
     */
    public function findByParents(array $parents): array
    {
        if ($parents === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('parent', $parents));
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute()->toArray();
    }

    /**
     * $uids plus every category below them, breadth first.
     *
     * Records relate to the terms a tree ends in, while a filter offers the
     * groups above them, so a selection has to carry its descendants to match
     * anything. Stops when a level adds nothing new, which also breaks a parent
     * cycle.
     *
     * @param int[] $uids
     *
     * @return int[]
     */
    public function findWithDescendants(array $uids): array
    {
        $collected = [];
        $pending = $this->findByUids($uids);

        while ($pending !== []) {
            foreach ($pending as $category) {
                $uid = $category->getUid();
                if ($uid !== null) {
                    $collected[] = $uid;
                }
            }

            $children = [];
            foreach ($this->findByParents($pending) as $child) {
                $uid = $child->getUid();
                if ($uid !== null && !in_array($uid, $collected, true)) {
                    $children[] = $child;
                }
            }

            $pending = $children;
        }

        return array_values(array_unique($collected));
    }

    /**
     * @param int[] $uids
     *
     * @return Category[]
     */
    protected function findByUids(array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $uids));

        return $query->execute()->toArray();
    }
}
