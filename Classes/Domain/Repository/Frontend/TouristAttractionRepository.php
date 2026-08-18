<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
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
            $constraints[] = $this->relatesToAnyOf($query, 'categories', $demand->getCategories());
        }
        // OR within keywords, AND against every other filter: one more entry in
        // $constraints is all that takes.
        if ($demand->getKeywords() !== []) {
            $constraints[] = $this->relatesToAnyOf($query, 'keywords', $demand->getKeywords());
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
     * Matches a record related to any of $uids or to anything below them.
     *
     * The mask offers the groups of a tree while records carry the terms those
     * groups contain, so an exact match on the selected uid alone would leave
     * every group selection empty.
     *
     * @param int[] $uids
     */
    protected function relatesToAnyOf(QueryInterface $query, string $property, array $uids): ConstraintInterface
    {
        $constraints = [];
        foreach ($this->categoryRepository->findWithDescendants($uids) as $uid) {
            $constraints[] = $query->contains($property, $uid);
        }

        return $query->logicalOr(...$constraints);
    }

    /**
     * Category tree for the search form: the categories used by attractions in
     * $storagePageIds, lifted to their set below $anchor and expanded to their
     * full subtrees, sorted by title per level.
     *
     * @param int[] $storagePageIds
     * @param int $anchor configured category parent; 0 offers nothing
     *
     * @return CategoryNode[]
     */
    public function findCategoryTreeForSearchForm(array $storagePageIds, int $anchor): array
    {
        return $this->findTreeForSearchForm(
            $storagePageIds,
            static fn (TouristAttraction $attraction): iterable => $attraction->getCategories(),
            $anchor
        );
    }

    /**
     * Keyword tree for the search form, built exactly like the category one but
     * from the keyword relation and against the keyword anchor.
     *
     * @param int[] $storagePageIds
     * @param int $anchor configured keyword parent; 0 offers nothing
     *
     * @return CategoryNode[]
     */
    public function findKeywordsTreeForSearchForm(array $storagePageIds, int $anchor): array
    {
        return $this->findTreeForSearchForm(
            $storagePageIds,
            static fn (TouristAttraction $attraction): iterable => $attraction->getKeywords(),
            $anchor
        );
    }

    /**
     * The sets directly below $anchor that $relation's categories belong to,
     * expanded to their full subtrees, sorted by title per level.
     *
     * The anchor is a container rather than a term an editor filters by, so the
     * tree starts one level beneath it. Bounding by the anchor is also what
     * keeps the trees apart: categories and keywords can share a storage folder,
     * and only the anchor distinguishes them. Harvesting from used relations
     * alone would let either tree leak into the other's form.
     *
     * @param int[] $storagePageIds
     * @param callable(TouristAttraction): iterable<Category> $relation
     * @param int $anchor
     *
     * @return CategoryNode[]
     */
    protected function findTreeForSearchForm(array $storagePageIds, callable $relation, int $anchor): array
    {
        if ($anchor <= 0) {
            return [];
        }

        $query = $this->createQuery();
        // Without a configured storage the default settings fall back to pid 0,
        // which matches nothing and would silently empty the whole mask — the
        // same guard findByDemand() carries.
        if ($storagePageIds !== []) {
            $query->getQuerySettings()->setStoragePageIds($storagePageIds);
        } else {
            $query->getQuerySettings()->setRespectStoragePage(false);
        }

        $roots = [];
        foreach ($query->execute() as $attraction) {
            if (!$attraction instanceof TouristAttraction) {
                continue;
            }
            foreach ($relation($attraction) as $category) {
                $root = $this->climbToAnchoredSet($category, $anchor);
                if ($root !== null && $root->getUid() !== null) {
                    $roots[$root->getUid()] = $root;
                }
            }
        }

        usort($roots, static fn (Category $a, Category $b): int => strcmp($a->getTitle(), $b->getTitle()));

        return array_map(fn (Category $root): CategoryNode => $this->buildNode($root), $roots);
    }

    /**
     * Climbs to the ancestor whose own parent is $anchor — the set the category
     * belongs to within that tree.
     *
     * Null when the rootline never passes $anchor, which is how a category from
     * another tree is skipped rather than offered. Guards against a parent
     * cycle, which would otherwise loop forever.
     */
    protected function climbToAnchoredSet(Category $category, int $anchor): ?Category
    {
        $seen = [];
        while (true) {
            $parent = $category->getParent();
            if ($parent instanceof Category && $parent->getUid() === $anchor) {
                return $category;
            }
            if (!$parent instanceof Category) {
                return null;
            }

            $uid = $category->getUid();
            if ($uid !== null && isset($seen[$uid])) {
                return null;
            }
            $seen[$uid ?? 0] = true;
            $category = $parent;
        }
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
            $this->categoryRepository->findByParents([$category])
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
