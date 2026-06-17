<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;

class TouristAttractionRepository extends Repository
{
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
