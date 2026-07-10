<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import;

use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class ImportLogger
{
    public const SEVERITY_DEBUG = 'debug';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_NOTICE = 'notice';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_ALERT = 'alert';
    public const SEVERITY_EMERGENCY = 'emergency';

    /**
     * PSR-3 severity ranking; higher number = higher severity. Used by
     * getMaxSeverity() so callers (Importer, Command) can decide on an exit
     * code without parsing strings.
     *
     * @var array<string, int>
     */
    private const SEVERITY_RANK = [
        self::SEVERITY_DEBUG => 0,
        self::SEVERITY_INFO => 1,
        self::SEVERITY_NOTICE => 2,
        self::SEVERITY_WARNING => 3,
        self::SEVERITY_ERROR => 4,
        self::SEVERITY_CRITICAL => 5,
        self::SEVERITY_ALERT => 6,
        self::SEVERITY_EMERGENCY => 7,
    ];

    private const UPDATE_WINDOW_SECONDS = 300;

    /**
     * Pending non-savingEntity entries staged via the record* methods.
     * Flushed alongside the savingEntity rows in writeLog(). Entries are
     * partial datamap rows minus the import_log FK, which writeLog wires up.
     *
     * @var list<array<string, mixed>>
     */
    private array $pendingEntries = [];

    /**
     * Highest severity seen across all record* calls in this run, in
     * SEVERITY_RANK terms. Reset by writeLog() so a logger instance can be
     * reused across runs without leaking state.
     */
    private int $maxSeverityRank = 0;

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * Stage one log entry per non-empty DataHandler errorLog line. DataHandler
     * does not surface the original SystemLogErrorClassification through
     * errorLog (only the formatted string), so we treat every captured line
     * as 'error' severity — when DataHandler raises something via log() with
     * $error > 0 it is by definition a thing that didn't land. Editors can
     * filter on severity in the BE list view.
     *
     * @param list<string> $errorLog Pass `$dataHandler->errorLog` here.
     */
    public function recordDataHandlerErrors(array $errorLog, int $iteration): void
    {
        foreach ($errorLog as $message) {
            $this->stage([
                'type' => 'dataHandlerError',
                'severity' => self::SEVERITY_ERROR,
                'message' => $message,
                'context' => (string)(json_encode(['iteration' => $iteration]) ?: '{}'),
            ]);
        }
    }

    /**
     * Stage one log entry for an exception caught by the importer (mapping
     * error during parse, fetch error during URL load, etc.). The type
     * argument matches the existing TCA values 'mappingError' / 'fetchingError'.
     */
    public function recordException(string $type, Throwable $exception): void
    {
        $context = [
            'class' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
        $this->stage([
            'type' => $type,
            'severity' => self::SEVERITY_ERROR,
            'message' => $exception->getMessage(),
            'context' => (string)(json_encode($context) ?: '{}'),
        ]);
    }

    /**
     * Stage one report entry per distinct source value per kind, de-duplicated
     * run-wide. Matched entries store the resolved uid (available only after
     * persist), not the title, so the report reads the current title live and
     * survives editor renames.
     *
     * @param list<array{kind: string, sourcePrefix: string, matched: array<string, string>, unmatched: list<string>}> $matchReports
     * @param array<string, string> $categoryUidBySourceValue prefixed source value → uid
     */
    public function recordMatchReports(array $matchReports, array $categoryUidBySourceValue = []): void
    {
        /** @var array<string, array<string, string>> $matchedByKind */
        $matchedByKind = [];
        /** @var array<string, array<string, true>> $unmatchedByKind */
        $unmatchedByKind = [];
        /** @var array<string, string> $prefixByKind */
        $prefixByKind = [];

        foreach ($matchReports as $report) {
            $kind = $report['kind'];
            $prefixByKind[$kind] = $report['sourcePrefix'];
            foreach ($report['matched'] as $value => $title) {
                $matchedByKind[$kind][$value] = $title;
            }
            foreach ($report['unmatched'] as $value) {
                $unmatchedByKind[$kind][$value] = true;
            }
        }

        foreach ($matchedByKind as $kind => $byValue) {
            foreach (array_keys($byValue) as $value) {
                // Rebuild the prefixed key the resolver staged the uid under.
                $prefixedValue = ($prefixByKind[$kind] ?? '') . $value;
                $resolvedKey = $categoryUidBySourceValue[$prefixedValue] ?? '';
                $recordUid = ctype_digit($resolvedKey) ? (int)$resolvedKey : 0;
                $this->stage([
                    'type' => 'categoryMatched',
                    'severity' => self::SEVERITY_INFO,
                    'kind' => $kind,
                    'remote_id' => $value,
                    'table_name' => 'sys_category',
                    'record_uid' => $recordUid,
                    'context' => (string)(json_encode(['kind' => $kind, 'matched' => true]) ?: '{}'),
                ]);
            }
        }

        foreach ($unmatchedByKind as $kind => $byValue) {
            foreach (array_keys($byValue) as $value) {
                $this->stage([
                    'type' => 'categoryUnmatched',
                    'severity' => self::SEVERITY_INFO,
                    'kind' => $kind,
                    'remote_id' => $value,
                    'context' => (string)(json_encode(['kind' => $kind, 'matched' => false]) ?: '{}'),
                ]);
            }
        }
    }

    public function getMaxSeverity(): string
    {
        $rank = $this->maxSeverityRank;
        foreach (self::SEVERITY_RANK as $severity => $value) {
            if ($value === $rank) {
                return $severity;
            }
        }
        return self::SEVERITY_DEBUG;
    }

    /**
     * Writes one tx_thuecat_import_log row plus one tx_thuecat_import_log_entry
     * per resolved record. NEW keys are inserts; existing-uid keys whose
     * tstamp falls within UPDATE_WINDOW_SECONDS are updates; older tstamps
     * are treated as unchanged and skipped. All entries staged via the
     * record* methods earlier in the run are flushed in the same DataHandler
     * call, so the import_log row contains the complete picture of one run.
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

        foreach ($this->pendingEntries as $entry) {
            $entry['import_log'] = $logKey;
            $datamap['tx_thuecat_import_log_entry'][StringUtility::getUniqueId('NEW')] = $entry;
        }

        $this->pendingEntries = [];

        if ($datamap['tx_thuecat_import_log_entry'] === []) {
            return;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();
    }

    /**
     * Reset accumulators so the logger can serve a second run cleanly.
     * Importer does not currently call this — each CLI invocation gets a
     * fresh instance via DI — but tests that reuse the logger do.
     */
    public function reset(): void
    {
        $this->pendingEntries = [];
        $this->maxSeverityRank = 0;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function stage(array $entry): void
    {
        $entry += [
            'pid' => 0,
            'kind' => '',
            'remote_id' => '',
            'table_name' => '',
            'record_uid' => 0,
            'insertion' => 0,
            'errors' => '[]',
        ];
        $this->pendingEntries[] = $entry;

        $severity = is_string($entry['severity'] ?? null) ? $entry['severity'] : self::SEVERITY_INFO;
        $rank = self::SEVERITY_RANK[$severity] ?? 0;
        if ($rank > $this->maxSeverityRank) {
            $this->maxSeverityRank = $rank;
        }
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
                'severity' => self::SEVERITY_INFO,
                'remote_id' => $remoteId,
                'table_name' => $table,
                'record_uid' => $resolvedUid,
                'insertion' => 1,
                'errors' => '[]',
                'message' => '',
                'context' => '{}',
            ];
        }

        $uid = (int)$key;
        if ($uid <= 0 || !$this->wasUpdatedRecently($table, $uid, $threshold)) {
            return null;
        }

        return [
            'pid' => 0,
            'type' => 'savingEntity',
            'severity' => self::SEVERITY_INFO,
            'remote_id' => $remoteId,
            'table_name' => $table,
            'record_uid' => $uid,
            'insertion' => 0,
            'errors' => '[]',
            'message' => '',
            'context' => '{}',
        ];
    }

    private function wasUpdatedRecently(string $table, int $uid, int $threshold): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new DeletedRestriction());
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
