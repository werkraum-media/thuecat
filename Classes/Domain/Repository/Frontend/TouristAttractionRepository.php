<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;
use WerkraumMedia\ThueCat\Service\FilterField\CategoryFilterField;
use WerkraumMedia\ThueCat\Service\FilterField\KeywordFilterField;
use WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\HierarchicalOptionProvider;

class TouristAttractionRepository extends AbstractThuecatObjectRepository
{
    protected TownRepository $townRepository;

    protected HierarchicalOptionProvider $hierarchicalOptionProvider;

    public function injectTownRepository(TownRepository $townRepository): void
    {
        $this->townRepository = $townRepository;
    }

    public function injectHierarchicalOptionProvider(HierarchicalOptionProvider $hierarchicalOptionProvider): void
    {
        $this->hierarchicalOptionProvider = $hierarchicalOptionProvider;
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
            $constraints[] = $this->matchesAnyTown($query, $demand->getTowns());
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
     * Matches an attraction carrying any of the given towns.
     *
     * `town` is a relation now, so `in()` — which compares a single-valued
     * column — cannot express membership; each town needs its own `contains()`.
     * The operands are Town objects: Extbase's plain-value helper happens to
     * pass an int through unchanged, but that is a type-juggling fallback, not
     * an interface it offers.
     *
     * A uid matching no stored town drops out. If every one drops out the
     * filter must still narrow to nothing, so fall back to a constraint that
     * cannot match rather than returning no constraint at all.
     *
     * @param int[] $uids
     */
    protected function matchesAnyTown(QueryInterface $query, array $uids): ConstraintInterface
    {
        $constraints = [];
        foreach ($uids as $uid) {
            $town = $this->townRepository->findByUid($uid);
            if ($town instanceof Town) {
                $constraints[] = $query->contains('towns', $town);
            }
        }

        if ($constraints === []) {
            return $query->equals('uid', 0);
        }

        return $query->logicalOr(...$constraints);
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
        foreach ($this->descendantsOf($property, $uids) as $uid) {
            $constraints[] = $query->contains($property, $uid);
        }

        return $query->logicalOr(...$constraints);
    }

    /**
     * The selected uids expanded to their subtrees, read by the same recursive
     * query the mask's options come from, so a filter matches exactly what the
     * mask offered.
     *
     * @param int[] $uids
     *
     * @return int[]
     */
    protected function descendantsOf(string $property, array $uids): array
    {
        $field = match ($property) {
            'categories' => new CategoryFilterField(),
            'keywords' => new KeywordFilterField(),
            default => null,
        };

        return $field === null
            ? $uids
            : $this->hierarchicalOptionProvider->descendantsOf($field, $uids);
    }
}
