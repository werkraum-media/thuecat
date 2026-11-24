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

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\ChattyInterface;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Moves the settings thuecat 7 stored as FlexForm XML into columns and URL records.
 *
 * Hidden and deleted configurations are migrated too, so restoring one later brings its settings back. A
 * configuration is written in one transaction together with emptying its XML, so a failed run leaves it unmigrated
 * rather than half migrated. A key this wizard does not know keeps that configuration's XML untouched; keys earlier
 * versions left behind are dropped.
 *
 * @phpstan-type Field array{vDEF?: string, el?: array<array-key, array{url: array{el: array<string, array{vDEF?: string}>}}>}
 * @phpstan-type ConfigurationRow array{uid: int|string, pid: int|string, configuration: string}
 */
#[UpgradeWizard('thuecat_importConfigurationFlexFormMigration')]
#[Autoconfigure(public: true)]
final class ImportConfigurationFlexFormMigration implements UpgradeWizardInterface, ChattyInterface
{
    private const string TABLE = 'tx_thuecat_import_configuration';

    private const string URL_TABLE = 'tx_thuecat_import_configuration_url';

    /** A run budget above this would let a single root take as long as a whole run did. */
    private const int MESSAGE_BUDGET_LIMIT = 300;

    private const array STRING_COLUMNS = [
        'fileFolder' => 'file_folder',
        'apiKey' => 'api_key',
        'syncScopeId' => 'sync_scope_id',
        'containsPlaceId' => 'contains_place_id',
        'syncChannelId' => 'sync_channel_id',
    ];

    private const array INTEGER_COLUMNS = [
        'storagePid' => 'storage_pid',
        'fetchCacheLifetime' => 'fetch_cache_lifetime',
    ];

    private const array OTHER_KEYS = ['urls', 'fetchLastXDays', 'runBudget', 'apiDomain'];

    /** The host thuecat 7 used for an empty API domain; dropping it changes nothing. */
    private const string DEFAULT_API_DOMAIN = 'https://cdb.thuecat.org';

    /** Read by no thuecat 7 code, but kept in stored XML by earlier versions; dropped without a report. */
    private const array OBSOLETE_KEYS = ['importTarget', 'categoryParent', 'categoryStoragePid'];

    private OutputInterface $output;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
        $this->output = new NullOutput();
    }

    public function getTitle(): string
    {
        return 'ThueCat: Move import configuration settings out of the FlexForm';
    }

    public function getDescription(): string
    {
        return 'Writes the settings of every import configuration, hidden and deleted ones included, to their own '
            . 'fields and its URLs to URL records. A configuration holding a setting this wizard does not know is '
            . 'reported and left as it is.';
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function executeUpdate(): bool
    {
        foreach ($this->configurationsWithXml() as $row) {
            $uid = (int)$row['uid'];
            $settings = $this->settingsOf($row['configuration']);
            $unknownKeys = array_diff(
                array_keys($settings),
                array_keys(self::STRING_COLUMNS),
                array_keys(self::INTEGER_COLUMNS),
                self::OTHER_KEYS,
                self::OBSOLETE_KEYS,
            );
            if ($unknownKeys !== []) {
                foreach ($unknownKeys as $key) {
                    $this->output->writeln(sprintf(
                        'Import configuration %d: unknown setting "%s", its settings were not migrated.',
                        $uid,
                        $key,
                    ));
                }

                continue;
            }

            $this->migrate($uid, (int)$row['pid'], $settings);
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->configurationsWithXml() !== [];
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array<string, Field> $settings
     */
    private function migrate(int $uid, int $pid, array $settings): void
    {
        $columns = ['configuration' => ''];
        foreach (self::STRING_COLUMNS as $key => $column) {
            $columns[$column] = $this->value($settings, $key);
        }

        foreach (self::INTEGER_COLUMNS as $key => $column) {
            $columns[$column] = (int)$this->value($settings, $key);
        }

        $fetchLastXDays = $this->value($settings, 'fetchLastXDays');
        $columns['fetch_last_x_days'] = $fetchLastXDays === '' ? 1 : (int)$fetchLastXDays;

        $runBudget = (int)$this->value($settings, 'runBudget');
        $columns['message_budget'] = $runBudget <= self::MESSAGE_BUDGET_LIMIT ? $runBudget : 0;
        if ($columns['message_budget'] !== $runBudget) {
            $this->output->writeln(sprintf(
                'Import configuration %d: the run budget of %d s exceeds %d s and was not carried over.',
                $uid,
                $runBudget,
                self::MESSAGE_BUDGET_LIMIT,
            ));
        }

        $apiDomain = $this->value($settings, 'apiDomain');
        if ($apiDomain !== '' && $apiDomain !== self::DEFAULT_API_DOMAIN) {
            $this->output->writeln(sprintf(
                'Import configuration %d: the API domain %s is dropped, hosts follow the configuration type.',
                $uid,
                $apiDomain,
            ));
        }

        $urls = $this->urlsOf($settings);
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->transactional(static function (Connection $connection) use ($uid, $pid, $columns, $urls): void {
            $connection->update(self::TABLE, $columns, ['uid' => $uid]);
            $now = time();
            foreach ($urls as $position => $url) {
                $connection->insert(self::URL_TABLE, [
                    'pid' => $pid,
                    'configuration' => $uid,
                    'sorting' => $position + 1,
                    'title' => $url['title'],
                    'url' => $url['url'],
                    'crdate' => $now,
                    'tstamp' => $now,
                ]);
            }
        });
    }

    /**
     * @param array<string, Field> $settings
     *
     * @return list<array{title: string, url: string}>
     */
    private function urlsOf(array $settings): array
    {
        $urls = [];
        foreach ($settings['urls']['el'] ?? [] as $entry) {
            $urls[] = [
                'title' => $this->value($entry['url']['el'], 'title'),
                'url' => $this->value($entry['url']['el'], 'url'),
            ];
        }

        return $urls;
    }

    /**
     * @param array<string, Field> $fields
     */
    private function value(array $fields, string $key): string
    {
        return trim($fields[$key]['vDEF'] ?? '');
    }

    /**
     * @return array<string, Field>
     */
    private function settingsOf(string $xml): array
    {
        /** @var array{data?: array{sDEF?: array{lDEF?: array<string, Field>}}} $flexForm */
        $flexForm = GeneralUtility::xml2array($xml);

        return $flexForm['data']['sDEF']['lDEF'] ?? [];
    }

    /**
     * @return list<ConfigurationRow>
     */
    private function configurationsWithXml(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        /** @var list<ConfigurationRow> $rows */
        $rows = $queryBuilder
            ->select('uid', 'pid', 'configuration')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->isNotNull('configuration'),
                $queryBuilder->expr()->neq('configuration', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return $rows;
    }
}
