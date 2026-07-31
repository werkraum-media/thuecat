<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\DateEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;

/**
 * Deletes stored occurrences an event's schedule no longer produces. Date rows
 * are keyed deterministically, so one that still exists upserts — without this,
 * one that stopped existing would linger forever.
 *
 * Event-import only: the POI path carries no event rows, so it returns early.
 */
#[Autoconfigure(public: true)]
class StaleDateReaper
{
    private readonly string $eventTable;

    private readonly string $dateTable;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
        // The entities own their table names; nothing else may hardcode them.
        $this->eventTable = (new EventEntity())->table;
        $this->dateTable = (new DateEntity())->table;
    }

    /**
     * Scoped twice over: to events this payload carries, so an event absent from
     * the run keeps its dates, and to rows the import owns — a backend-created
     * row has no `<eventRemoteId>::date::…` remote_id and must survive.
     */
    public function reap(DataHandlerPayload $payload): void
    {
        $dataMap = $payload->getDataMap();
        $eventRows = $dataMap[$this->eventTable] ?? [];
        if ($eventRows === []) {
            return;
        }

        $importedDates = [];
        foreach ($dataMap[$this->dateTable] ?? [] as $dateRow) {
            $remoteId = (string)($dateRow['remote_id'] ?? '');
            if ($remoteId !== '') {
                $importedDates[$remoteId] = true;
            }
        }

        foreach ($eventRows as $eventRow) {
            $eventRemoteId = (string)($eventRow['remote_id'] ?? '');
            if ($eventRemoteId === '') {
                continue;
            }

            foreach ($this->findOwnedDates($eventRemoteId) as $dateUid => $dateRemoteId) {
                if (!isset($importedDates[$dateRemoteId])) {
                    $payload->addCmdMap($this->dateTable, (string)$dateUid, 'delete', 1);
                }
            }
        }
    }

    /**
     * @return array<int, string> uid => remote_id
     */
    private function findOwnedDates(string $eventRemoteId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($this->dateTable);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;
        $rows = $queryBuilder->select('uid', 'remote_id')
            ->from($this->dateTable)
            ->where(
                $queryBuilder->expr()->like(
                    'remote_id',
                    $queryBuilder->createNamedParameter(
                        $queryBuilder->escapeLikeWildcards($eventRemoteId . '::date::') . '%'
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $byUid = [];
        foreach ($rows as $row) {
            $uid = (int)(is_numeric($row['uid']) ? $row['uid'] : 0);
            $remoteId = $row['remote_id'] ?? '';
            if ($uid > 0 && is_string($remoteId)) {
                $byUid[$uid] = $remoteId;
            }
        }
        return $byUid;
    }
}
