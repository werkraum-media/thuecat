<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Category;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\CategoryNode;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;

class TouristAttractionRepository extends Repository
{
    protected CategoryRepository $categoryRepository;

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function findByDemand(TouristAttractionDemand $demand): QueryResultInterface
    {
        $query = $this->createQuery();

        $settings = $query->getQuerySettings();
        if (array_filter($settings->getStoragePageIds()) === []) {
            $settings->setRespectStoragePage(false);
        }

        //@todo each new filter needs its own constraint here
        $constraints = [];
        if ($demand->getSearchword() !== '') {
            $constraints[] = $query->like('title', '%' . $demand->getSearchword() . '%');
        }
        if ($demand->getTowns() !== []) {
            $constraints[] = $query->in('town', $demand->getTowns());
        }
        if ($demand->getCategories() !== []) {
            $categoryConstraints = [];
            foreach ($demand->getCategories() as $category) {
                $categoryConstraints[] = $query->contains('categories', $category);
            }
            $constraints[] = $query->logicalOr(...$categoryConstraints);
        }
        if ($demand->getPetsAllowed()) {
            $constraints[] = $query->equals('petsAllowed', 'true');
        }
        if ($demand->getIsAccessibleForFree()) {
            $constraints[] = $query->equals('isAccessibleForFree', 'true');
        }
        if ($demand->getPublicAccess()) {
            $constraints[] = $query->equals('publicAccess', 'true');
        }

        if ($constraints !== []) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Returns the given records in the exact order of the passed uids.
     *
     * @param int[] $uids
     *
     * @return TouristAttraction[]
     */
    public function findBySelectedRecords(array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $uids));

        $byUid = [];
        foreach ($query->execute() as $attraction) {
            if ($attraction instanceof TouristAttraction && $attraction->getUid() !== null) {
                $byUid[$attraction->getUid()] = $attraction;
            }
        }

        $ordered = [];
        foreach ($uids as $uid) {
            if (isset($byUid[$uid])) {
                $ordered[] = $byUid[$uid];
            }
        }

        return $ordered;
    }

    /**
     * Category tree for the search form: the roots of the categories used by
     * attractions in $storagePageIds, expanded to their full subtrees, sorted by
     * title per level.
     *
     * @param int[] $storagePageIds
     *
     * @return CategoryNode[]
     */
    public function findCategoryTreeForSearchForm(array $storagePageIds): array
    {
        $query = $this->createQuery();
        if ($storagePageIds !== []) {
            $query->getQuerySettings()->setStoragePageIds($storagePageIds);
        }

        $roots = [];
        foreach ($query->execute() as $attraction) {
            if (!$attraction instanceof TouristAttraction) {
                continue;
            }
            foreach ($attraction->getCategories() as $category) {
                $root = $this->climbToRoot($category);
                if ($root->getUid() !== null) {
                    $roots[$root->getUid()] = $root;
                }
            }
        }

        usort($roots, static fn (Category $a, Category $b): int => strcmp($a->getTitle(), $b->getTitle()));

        return array_map(fn (Category $root): CategoryNode => $this->buildNode($root), $roots);
    }

    /**
     * Guards against a parent cycle, which would otherwise loop forever.
     */
    protected function climbToRoot(Category $category): Category
    {
        $seen = [];
        while (($parent = $category->getParent()) instanceof Category) {
            $uid = $category->getUid();
            if ($uid !== null && isset($seen[$uid])) {
                break;
            }
            $seen[$uid ?? 0] = true;
            $category = $parent;
        }

        return $category;
    }

    /**
     * @param int[] $ancestors uids on the path to $category, to survive a cycle
     */
    protected function buildNode(Category $category, array $ancestors = []): CategoryNode
    {
        $uid = $category->getUid();
        if ($uid === null || in_array($uid, $ancestors, true)) {
            return new CategoryNode($category, []);
        }

        $ancestors[] = $uid;
        $children = array_map(
            fn (Category $child): CategoryNode => $this->buildNode($child, $ancestors),
            $this->categoryRepository->findByParents([$uid])
        );

        return new CategoryNode($category, $children);
    }

    /**
     * Distinct towns of attractions within $storagePageIds, sorted by title — the
     * search form's town options scoped to what a list on those pages can return.
     *
     * @param int[] $storagePageIds
     *
     * @return Town[]
     */
    public function findTownsInStorageSortedByTitle(array $storagePageIds): array
    {
        $query = $this->createQuery();
        if ($storagePageIds !== []) {
            $query->getQuerySettings()->setStoragePageIds($storagePageIds);
        }
        $query->setOrderings(['town.title' => QueryInterface::ORDER_ASCENDING]);

        $towns = [];
        foreach ($query->execute() as $attraction) {
            $town = $attraction instanceof TouristAttraction ? $attraction->getTown() : null;
            if ($town instanceof Town && $town->getUid() !== null) {
                $towns[$town->getUid()] = $town;
            }
        }

        return array_values($towns);
    }
}
