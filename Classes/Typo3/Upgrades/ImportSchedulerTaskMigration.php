<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Typo3\Upgrades;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Upgrades\ChattyInterface;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\CronCommand\CronCommand;

/**
 * Moves the schedule of every scheduler task that runs thuecat 7's import command onto the configuration it names,
 * and deletes the task.
 *
 * The task with the shortest interval decides a configuration's schedule; a disabled task counts only when no other
 * runs the configuration, and leaves it manual only. A cron expression becomes the gap between two of its
 * occurrences. Configurations without a recorded run get their last runs from their import logs, so the first job
 * of a sync channel is not a full import.
 *
 * @phpstan-type TaskRow array{uid: int|string, parameters: string, execution_details: string, nextexecution: int|string, disabled: int|string}
 * @phpstan-type Task array{uid: int, configuration: int, enabled: bool, interval: int, nextRun: int, skipMedia: bool}
 */
#[UpgradeWizard('thuecat_importSchedulerTaskMigration')]
#[Autoconfigure(public: true)]
final class ImportSchedulerTaskMigration implements UpgradeWizardInterface, ChattyInterface
{
    private const string TASK_TABLE = 'tx_scheduler_task';

    private const string CONFIGURATION_TABLE = 'tx_thuecat_import_configuration';

    private const string COMMAND = 'thuecat:importviaconfiguration';

    /** What made a thuecat 7 import log unfit to start a sync window from. */
    private const array DISQUALIFYING_ENTRY_TYPES = ['runAborted', 'runFailed', 'fetchingError'];

    private OutputInterface $output;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
        $this->output = new NullOutput();
    }

    public function getTitle(): string
    {
        return 'ThueCat: Move import scheduler tasks onto their configurations';
    }

    public function getDescription(): string
    {
        return 'Writes the interval and next execution of every scheduler task that runs '
            . '"thuecat:importviaconfiguration" to the configuration it names and deletes the task. The option '
            . '--fresh, cron expressions and configurations run by several tasks are reported. Record the tasks before '
            . 'running it: it cannot be undone.';
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function executeUpdate(): bool
    {
        $tasksByConfiguration = [];
        foreach ($this->taskRows() as $row) {
            $task = $this->taskOf($row);
            $tasksByConfiguration[$task['configuration']][] = $task;
        }

        foreach ($tasksByConfiguration as $configuration => $tasks) {
            $this->migrate($configuration, $tasks);
        }

        $this->seedLastRuns();

        return true;
    }

    /**
     * An import task stored the way of earlier TYPO3 versions has no task type until core's storage migration gives
     * it one. Core's own answer is no guide: it keeps counting native tasks it has already migrated.
     */
    public function updateNecessary(): bool
    {
        return !$this->hasUnmigratedImportTasks()
            && ($this->taskRows() !== [] || $this->configurationsToSeed() !== []);
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param list<Task> $tasks
     */
    private function migrate(int $configuration, array $tasks): void
    {
        $enabled = array_filter($tasks, static fn (array $task): bool => $task['enabled']);
        usort($enabled, static fn (array $a, array $b): int => $a['interval'] <=> $b['interval']);
        $winner = $enabled[0] ?? null;
        if ($winner !== null) {
            $this->connectionPool->getConnectionForTable(self::CONFIGURATION_TABLE)->update(
                self::CONFIGURATION_TABLE,
                ['schedule_interval' => $winner['interval'], 'next_run' => $winner['nextRun'], 'skip_media' => (int)$winner['skipMedia']],
                ['uid' => $configuration]
            );
            if (count($tasks) > 1) {
                $this->output->writeln(sprintf(
                    'Import configuration %d: scheduler tasks %s ran it, the interval of %d s of task %d was kept.',
                    $configuration,
                    implode(', ', array_column($tasks, 'uid')),
                    $winner['interval'],
                    $winner['uid'],
                ));
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TASK_TABLE);
        $queryBuilder
            ->update(self::TASK_TABLE)
            ->set($this->tcaSchemaFactory->get(self::TASK_TABLE)->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName(), 1)
            ->where($queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter(array_column($tasks, 'uid'), ArrayParameterType::INTEGER)))
            ->executeStatement()
        ;
    }

    /**
     * @param TaskRow $row
     *
     * @return Task
     */
    private function taskOf(array $row): array
    {
        // Core stores every option of the command and every timing detail.
        // Tasks created before the command had options store none.
        /** @var array{arguments: array{configuration: int|string}, options?: array{fresh?: bool, no_media?: bool}} $parameters */
        $parameters = json_decode($row['parameters'], true);
        /** @var array{interval: int, cronCmd: string} $execution */
        $execution = json_decode($row['execution_details'], true);
        $uid = (int)$row['uid'];
        $configuration = (int)$parameters['arguments']['configuration'];
        $nextRun = (int)$row['nextexecution'];
        $interval = $execution['interval'];

        $cron = $execution['cronCmd'];
        if ($cron !== '') {
            $interval = $this->cronGap($cron, $nextRun);
            $this->output->writeln(sprintf(
                'Scheduler task %d: the cron expression "%s" of import configuration %d became an interval of %d s.',
                $uid,
                $cron,
                $configuration,
                $interval,
            ));
        }

        if ($parameters['options']['fresh'] ?? false) {
            $this->output->writeln(sprintf(
                'Scheduler task %d: the option --fresh of import configuration %d was not carried over.',
                $uid,
                $configuration,
            ));
        }

        return [
            'uid' => $uid,
            'configuration' => $configuration,
            'enabled' => (int)$row['disabled'] === 0,
            'interval' => $interval,
            'nextRun' => $nextRun,
            'skipMedia' => $parameters['options']['no_media'] ?? false,
        ];
    }

    /**
     * The gap between the two occurrences after the task's next execution.
     */
    private function cronGap(string $cron, int $from): int
    {
        $first = new CronCommand($cron, $from);
        $first->calculateNextValue();

        $second = new CronCommand($cron, $first->getTimestamp());
        $second->calculateNextValue();

        return $second->getTimestamp() - $first->getTimestamp();
    }

    private function seedLastRuns(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::CONFIGURATION_TABLE);
        foreach ($this->configurationsToSeed() as $configuration) {
            $connection->update(
                self::CONFIGURATION_TABLE,
                ['last_run' => $this->newestLog($configuration, false), 'last_successful_run' => $this->newestLog($configuration, true)],
                ['uid' => $configuration]
            );
        }
    }

    /**
     * @return list<int> configurations with import logs but no recorded run, hidden and deleted ones included
     */
    private function configurationsToSeed(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::CONFIGURATION_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        /** @var list<int|string> $uids */
        $uids = $queryBuilder
            ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier('configuration.uid'))
            ->from(self::CONFIGURATION_TABLE, 'configuration')
            ->join(
                'configuration',
                'tx_thuecat_import_log',
                'log',
                $queryBuilder->expr()->eq('log.configuration', $queryBuilder->quoteIdentifier('configuration.uid'))
            )
            ->where($queryBuilder->expr()->eq('configuration.last_run', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        return array_map(intval(...), $uids);
    }

    private function newestLog(int $configuration, bool $successful): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_log');
        $queryBuilder
            ->select('log.crdate')
            ->from('tx_thuecat_import_log', 'log')
            ->where($queryBuilder->expr()->eq('log.configuration', $queryBuilder->createNamedParameter($configuration, Connection::PARAM_INT)))
            ->orderBy('log.crdate', 'DESC')
            ->setMaxResults(1)
        ;
        if ($successful) {
            $queryBuilder
                ->leftJoin(
                    'log',
                    'tx_thuecat_import_log_entry',
                    'entry',
                    (string)$queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('entry.import_log', $queryBuilder->quoteIdentifier('log.uid')),
                        $queryBuilder->expr()->in('entry.type', $queryBuilder->createNamedParameter(self::DISQUALIFYING_ENTRY_TYPES, ArrayParameterType::STRING))
                    )
                )
                ->andWhere($queryBuilder->expr()->isNull('entry.uid'))
            ;
        }

        /** @var int|string|false $crdate */
        $crdate = $queryBuilder->executeQuery()->fetchOne();

        return (int)$crdate;
    }

    // The serialized object is a BLOB, which not every database matches with LIKE.
    private function hasUnmigratedImportTasks(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TASK_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $deleted = $this->tcaSchemaFactory->get(self::TASK_TABLE)->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();

        $serializedTasks = $queryBuilder
            ->select('serialized_task_object')
            ->from(self::TASK_TABLE)
            ->where(
                $queryBuilder->expr()->eq($deleted, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('tasktype', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('tasktype')
                )
            )
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        foreach ($serializedTasks as $serializedTask) {
            if (is_string($serializedTask) && str_contains($serializedTask, self::COMMAND)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deleted tasks are left alone, disabled ones are read.
     *
     * @return list<TaskRow>
     */
    private function taskRows(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TASK_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $deleted = $this->tcaSchemaFactory->get(self::TASK_TABLE)->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();

        /** @var list<TaskRow> $rows */
        $rows = $queryBuilder
            ->select('uid', 'parameters', 'execution_details', 'nextexecution')
            ->addSelect($this->tcaSchemaFactory->get(self::TASK_TABLE)->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName() . ' AS disabled')
            ->from(self::TASK_TABLE)
            ->where(
                $queryBuilder->expr()->eq('tasktype', $queryBuilder->createNamedParameter(self::COMMAND)),
                $queryBuilder->expr()->eq($deleted, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return $rows;
    }
}
