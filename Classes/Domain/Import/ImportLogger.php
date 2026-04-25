<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class ImportLogger
{
    private const UPDATE_WINDOW_SECONDS = 300;

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * Writes one tx_thuecat_import_log row plus one tx_thuecat_import_log_entry
     * per resolved record. NEW keys are inserts; existing-uid keys whose
     * tstamp falls within UPDATE_WINDOW_SECONDS are updates; older tstamps
     * are treated as unchanged and skipped.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $payload
     * @param array<string, int|string> $substNEWwithIDs
     */
    public function writeLog(int|null $configurationUid, array $payload, array $substNEWwithIDs): void
    {
        $logKey = StringUtility::getUniqueId('NEW');
        $datamap = [
            'tx_thuecat_import_log' => [
                $logKey => [
                    'pid' => 0,
                    'configuration' => (int)$configurationUid,
                ],
            ],
            'tx_thuecat_import_log_entry' => [],
        ];

        $threshold = time() - self::UPDATE_WINDOW_SECONDS;

        foreach ($payload as $table => $rows) {
            foreach ($rows as $key => $row) {
                $entry = $this->buildEntry($table, (string)$key, $row, $substNEWwithIDs, $threshold);
                if ($entry === null) {
                    continue;
                }
                $entry['import_log'] = $logKey;
                $datamap['tx_thuecat_import_log_entry'][StringUtility::getUniqueId('NEW')] = $entry;
            }
        }

        if ($datamap['tx_thuecat_import_log_entry'] === []) {
            return;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int|string> $substNEWwithIDs
     *
     * @return array<string, mixed>|null
     */
    private function buildEntry(string $table, string $key, array $row, array $substNEWwithIDs, int $threshold): ?array
    {
        $remoteId = is_string($row['remote_id'] ?? null) ? $row['remote_id'] : '';

        if (str_starts_with($key, 'NEW')) {
            $resolvedUid = (int)($substNEWwithIDs[$key] ?? 0);
            if ($resolvedUid <= 0) {
                return null;
            }
            return [
                'pid' => 0,
                'type' => 'savingEntity',
                'remote_id' => $remoteId,
                'table_name' => $table,
                'record_uid' => $resolvedUid,
                'insertion' => 1,
                'errors' => '[]',
            ];
        }

        $uid = (int)$key;
        if ($uid <= 0 || !$this->wasUpdatedRecently($table, $uid, $threshold)) {
            return null;
        }

        return [
            'pid' => 0,
            'type' => 'savingEntity',
            'remote_id' => $remoteId,
            'table_name' => $table,
            'record_uid' => $uid,
            'insertion' => 0,
            'errors' => '[]',
        ];
    }

    private function wasUpdatedRecently(string $table, int $uid, int $threshold): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $tstamp = $queryBuilder
            ->select('tstamp')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($tstamp) && (int)$tstamp >= $threshold;
    }
}
