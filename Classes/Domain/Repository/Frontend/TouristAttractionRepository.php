<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class TouristAttractionRepository extends Repository
{


    public function findCustom(array $storagePids): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setStoragePageIds($storagePids);
//        $typo3DbQueryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
//            $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
//            DebuggerUtility::var_dump($queryBuilder->getSQL());
//            DebuggerUtility::var_dump($queryBuilder->getParameters());
        return $query->execute();
    }
}