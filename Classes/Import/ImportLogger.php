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
use WerkraumMedia\ThueCat\Import\Http\RetryExhaustedException;
use WerkraumMedia\ThueCat\Import\Http\RetryTally;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Watchdog\RunBudgetExhaustedException;

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
     * Held apart from pendingEntries so it can be written first, whenever the
     * importer resolves it. Cleared by writeLog() along with the rest.
     *
     * @var array<string, mixed>|null
     */
    private ?array $effectiveSettings = null;

    /**
     * Highest severity seen across all record* calls in this run, in
     * SEVERITY_RANK terms. Reset by writeLog() so a logger instance can be
     * reused across runs without leaking state.
     */
    private int $maxSeverityRank = 0;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RetryTally $retryTally
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
     *
     * The root URL goes into remote_id so the abandoned root is identifiable
     * from the log alone. A record-level remote id is not reliably known here:
     * the raise may happen before any row was keyed, and claiming a record we
     * cannot name would be worse than naming none.
     */
    public function recordException(string $type, Throwable $exception, string $url = ''): void
    {
        $context = [
            'class' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => $url,
        ] + $this->retryContext($exception);
        $this->stage([
            'type' => $type,
            'severity' => self::SEVERITY_ERROR,
            'remote_id' => $url,
            'message' => $exception->getMessage(),
            'context' => (string)(json_encode($context) ?: '{}'),
        ]);
    }

    /**
     * Machine-readable cause and attempt count, so an operator can tell a
     * timed-out host from a rejected request without parsing the message.
     *
     * @return array<string, int|string>
     */
    private function retryContext(Throwable $exception): array
    {
        $exhausted = $exception instanceof RetryExhaustedException
            ? $exception
            : $exception->getPrevious();

        if (!$exhausted instanceof RetryExhaustedException) {
            return [];
        }

        return [
            'cause' => 'retryExhausted',
            'attempts' => $exhausted->attempts,
        ];
    }

    public function recordRunFailed(Throwable $failure, ImportPhase $phaseReached): void
    {
        $this->stage([
            'type' => 'runFailed',
            'severity' => self::SEVERITY_ERROR,
            'message' => $failure->getMessage(),
            'context' => (string)(json_encode([
                'phase' => $phaseReached->value,
                'class' => $failure::class,
                'code' => $failure->getCode(),
                'file' => $failure->getFile(),
                'line' => $failure->getLine(),
            ]) ?: '{}'),
        ]);
    }

    /**
     * One summary per run rather than one entry per request: a degrading
     * upstream would otherwise bury the log it is meant to warn through.
     * Notice, not warning — the resources were fetched, so the run's severity
     * and exit code must not move.
     */
    private function recordRecoveredRetries(): void
    {
        if (!$this->retryTally->hasRecoveries()) {
            return;
        }

        $recovered = $this->retryTally->recoveredRequests();
        $this->stage([
            'type' => 'retriesRecovered',
            'severity' => self::SEVERITY_NOTICE,
            'message' => sprintf(
                '%d request(s) succeeded only after being retried, costing %d extra attempt(s).',
                $recovered,
                $this->retryTally->wastedAttempts()
            ),
            'context' => (string)(json_encode([
                'recoveredRequests' => $recovered,
                'wastedAttempts' => $this->retryTally->wastedAttempts(),
            ]) ?: '{}'),
        ]);
        $this->retryTally->reset();
    }

    // The phase reached is the diagnosis: it says where the run ran out.
    public function recordRunAborted(RunBudgetExhaustedException $exception): void
    {
        $this->stage([
            'type' => 'runAborted',
            'severity' => self::SEVERITY_ERROR,
            'message' => $exception->getMessage(),
            'context' => (string)(json_encode([
                'phase' => $exception->phase->value,
                'budgetSeconds' => $exception->budgetSeconds,
                'elapsedSeconds' => round($exception->elapsedSeconds, 1),
            ]) ?: '{}'),
        ]);
    }

    /**
     * Stage one entry per parent that lost a reference. Warning, not error:
     * the owner still imports, only the relation is missing.
     */
    public function recordSkippedReference(
        string $ownerTable,
        string $ownerRemoteId,
        string $field,
        string $url,
        string $reason
    ): void {
        $this->stage([
            'type' => 'referenceSkipped',
            'severity' => self::SEVERITY_WARNING,
            'remote_id' => $ownerRemoteId,
            'table_name' => $ownerTable,
            'message' => sprintf(
                'Skipped reference "%s" for field "%s": %s',
                $url,
                $field,
                $reason
            ),
            'context' => (string)(json_encode([
                'url' => $url,
                'field' => $field,
                'reason' => $reason,
            ]) ?: '{}'),
        ]);
    }

    /**
     * A reference whose record imported into a table the bucket cannot relate
     * to. Upstream mixes kinds in one property (a POI under a key that maps to
     * towns, say), so this is data drift rather than a fault: the record is
     * imported, only the relation is dropped. Info severity keeps a healthy run
     * reporting healthy while the entry stays queryable.
     */
    public function recordUnrelatableReference(
        string $ownerTable,
        string $ownerRemoteId,
        string $field,
        string $url,
        string $expectedTable,
        string $actualTable
    ): void {
        $this->stage([
            'type' => 'referenceUnrelatable',
            'severity' => self::SEVERITY_INFO,
            'remote_id' => $ownerRemoteId,
            'table_name' => $ownerTable,
            'message' => sprintf(
                'Reference "%s" for field "%s" was imported as %s, which cannot be related to %s.',
                $url,
                $field,
                $actualTable,
                $expectedTable
            ),
            'context' => (string)(json_encode([
                'url' => $url,
                'field' => $field,
                'expectedTable' => $expectedTable,
                'actualTable' => $actualTable,
            ]) ?: '{}'),
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

    /**
     * One notice per `table.field` whose entity produced categories but whose
     * schema lacks that relation field. Lets a model opt out of category
     * mapping without failing the write. Keys are the resolver's set keys.
     *
     * @param array<string, true> $tableFields set of "table.field" keys
     */
    /**
     * The values driving this run, as resolved. Written as the log's first
     * entry, so a reader sees what governed the run before its results, and a
     * dataset can rely on the position. DEBUG is rank 0: a clean run reports
     * 'debug', so anything higher would make the summary change the run's own
     * outcome. The API key is absent from $settings by construction, so no
     * masking happens here.
     *
     * @param array<string, string|int> $settings setting name => effective value
     */
    public function recordEffectiveSettings(array $settings): void
    {
        $this->effectiveSettings = [
            'pid' => 0,
            'kind' => '',
            'remote_id' => '',
            'table_name' => '',
            'record_uid' => 0,
            'insertion' => 0,
            'errors' => '[]',
            'type' => 'effectiveSettings',
            'severity' => self::SEVERITY_DEBUG,
            'message' => 'Effective settings for this run.',
            'context' => (string)(json_encode($settings) ?: '{}'),
        ];
    }

    public function recordCategoriesFieldMissing(array $tableFields): void
    {
        foreach (array_keys($tableFields) as $tableField) {
            $this->stage([
                'type' => 'categoriesFieldMissing',
                'severity' => self::SEVERITY_NOTICE,
                'message' => 'Categories were mapped but "' . $tableField . '" does not exist; mapping skipped.',
                'context' => (string)(json_encode(['tableField' => $tableField]) ?: '{}'),
            ]);
        }
    }

    /**
     * One entry per event whose schedule named a day no series can be built
     * from. Warning, not error: the event still imports, only that day is lost.
     *
     * @param array<string, list<string>> $daysByEvent event remote_id => day values
     */
    public function recordUnusableScheduleDays(array $daysByEvent): void
    {
        foreach ($daysByEvent as $eventRemoteId => $days) {
            $this->stage([
                'type' => 'scheduleDaySkipped',
                'severity' => self::SEVERITY_WARNING,
                'remote_id' => $eventRemoteId,
                'table_name' => 'tx_events_domain_model_event',
                'message' => sprintf(
                    'Skipped schedule day(s) "%s": no date series can be built from them.',
                    implode('", "', $days)
                ),
                'context' => (string)(json_encode(['days' => $days]) ?: '{}'),
            ]);
        }
    }

    /**
     * One entry per event whose schedule supplied more usable weekdays than the
     * import can carry. The days are valid; the limit is on our side.
     *
     * @param array<string, list<string>> $daysByEvent event remote_id => weekday names
     */
    public function recordDroppedScheduleDays(array $daysByEvent): void
    {
        foreach ($daysByEvent as $eventRemoteId => $days) {
            $this->stage([
                'type' => 'scheduleDayDropped',
                'severity' => self::SEVERITY_WARNING,
                'remote_id' => $eventRemoteId,
                'table_name' => 'tx_events_domain_model_event',
                'message' => sprintf(
                    'Dropped schedule day(s) "%s": the schedule carries only one.',
                    implode('", "', $days)
                ),
                'context' => (string)(json_encode(['days' => $days]) ?: '{}'),
            ]);
        }
    }

    /**
     * One entry per event that finished the import with no dates. Warning, not
     * error: the event imported, it just cannot be displayed.
     *
     * @param array<string, string> $titleByEvent event remote_id => title
     */
    public function recordEventsWithoutDates(array $titleByEvent): void
    {
        foreach ($titleByEvent as $eventRemoteId => $title) {
            // No title in the imported language would render 'Event ""'.
            $name = $title !== '' ? $title : $eventRemoteId;
            $this->stage([
                'type' => 'eventWithoutDates',
                'severity' => self::SEVERITY_WARNING,
                'remote_id' => $eventRemoteId,
                'table_name' => 'tx_events_domain_model_event',
                'message' => sprintf('Event "%s" was imported without any dates.', $name),
                'context' => (string)(json_encode(['title' => $title]) ?: '{}'),
            ]);
        }
    }

    /**
     * One entry per event publishing event-level date keys that could not be
     * resolved. Such an event also ends up dateless, so it gets an
     * eventWithoutDates entry too: this one says a published value was
     * unusable, that one says the record is undisplayable.
     *
     * @param array<string, string> $titleByEvent event remote_id => title
     */
    public function recordUnresolvableEventDates(array $titleByEvent): void
    {
        foreach ($titleByEvent as $eventRemoteId => $title) {
            $name = $title !== '' ? $title : $eventRemoteId;
            $this->stage([
                'type' => 'eventDateSkipped',
                'severity' => self::SEVERITY_WARNING,
                'remote_id' => $eventRemoteId,
                'table_name' => 'tx_events_domain_model_event',
                'message' => sprintf(
                    'Event "%s" publishes a date that could not be resolved: skipped.',
                    $name
                ),
                'context' => (string)(json_encode(['title' => $title]) ?: '{}'),
            ]);
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
        $this->recordRecoveredRetries();
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

        // First entry of the run, so the log opens with what drove it.
        if ($this->effectiveSettings !== null) {
            $this->effectiveSettings['import_log'] = $logKey;
            $datamap['tx_thuecat_import_log_entry'][StringUtility::getUniqueId('NEW')] = $this->effectiveSettings;
            $this->effectiveSettings = null;
        }

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
        $this->retryTally->reset();
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
