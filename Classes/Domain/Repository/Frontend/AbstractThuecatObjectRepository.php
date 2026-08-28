<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;

class AbstractThuecatObjectRepository extends Repository
{
    /**
     * Returns the given records in the exact order of the passed uids.
     *
     * @param int[] $uids
     *
     * @return Base[]
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
            if ($attraction instanceof Base && $attraction->getUid() !== null) {
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
}
