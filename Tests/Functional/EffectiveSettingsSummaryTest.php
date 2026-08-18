<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

// Values driving a run are spread over site settings, extension configuration
// and the import configuration, so each run reports what it actually used.
class EffectiveSettingsSummaryTest extends AbstractImportTestCase
{
    /** @var array<mixed> */
    private array $extensionConfigurationBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionConfigurationBackup = $this->currentExtensionConfiguration();
    }

    // The suite runs with backupGlobals="false", so an api key set here would
    // otherwise persist into every later test.
    protected function tearDown(): void
    {
        $this->storeExtensionConfiguration($this->extensionConfigurationBackup);
        parent::tearDown();
    }

    /**
     * @return array<mixed>
     */
    private function currentExtensionConfiguration(): array
    {
        $confVars = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        if (!is_array($confVars) || !is_array($confVars['EXTENSIONS'] ?? null)) {
            return [];
        }

        $configuration = $confVars['EXTENSIONS']['thuecat'] ?? [];

        return is_array($configuration) ? $configuration : [];
    }

    /**
     * @param array<mixed> $configuration
     */
    private function storeExtensionConfiguration(array $configuration): void
    {
        $confVars = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        if (!is_array($confVars)) {
            $confVars = [];
        }
        $extensions = is_array($confVars['EXTENSIONS'] ?? null) ? $confVars['EXTENSIONS'] : [];
        $extensions['thuecat'] = $configuration;
        $confVars['EXTENSIONS'] = $extensions;
        $GLOBALS['TYPO3_CONF_VARS'] = $confVars;
    }

    #[Test]
    public function everyRunRecordsOneSummary(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('effectiveSettings');

        self::assertCount(1, $entries, 'One summary per run.');
        self::assertSame('debug', $entries[0]['severity']);
    }

    // 'debug' is rank 0, so the summary cannot lift a clean run's outcome.
    #[Test]
    public function theSummaryDoesNotRaiseTheRunSeverity(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('debug', $severity);
    }

    /**
     * Asserts the complete key set, not just presence: an anchor kind added to
     * CategoryAnchorSetting but not reported fails here, and so does a
     * reported setting nobody expected — including a stray apiKey.
     */
    #[Test]
    public function theSummaryNamesTheSettingsThatDriveTheRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $expected = ['storagePid', 'fileFolder', 'apiDomain'];
        // Anchors come from the enum, so a new pair is covered without editing
        // this list — it only has to be reported.
        foreach (CategoryAnchorSetting::cases() as $anchor) {
            $expected[] = $anchor->settingsPath();
        }
        $expected = array_merge($expected, [
            'readTimeout',
            'connectTimeout',
            'maxAttempts',
            'runBudget',
            'fetchCacheLifetime',
        ]);

        sort($expected);
        $actual = array_keys($this->summaryContext());
        sort($actual);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function unsetAnchorsAreReportedAsUnsetNotZero(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $context = $this->summaryContext();

        // This fixture configures no anchors at all, so every kind the enum
        // knows must report 'unset' — a new pair is covered automatically.
        foreach (CategoryAnchorSetting::cases() as $anchor) {
            self::assertSame(
                'unset',
                $context[$anchor->settingsPath()] ?? null,
                $anchor->settingsPath() . ' should report as unset.'
            );
        }
    }

    /**
     * Distinctive enough that any rendering of it — whole, masked or measured
     * — is recognisable in the serialised entry.
     */
    private const API_KEY = 'sekrit-api-key-9f3a2b';

    #[Test]
    public function theApiKeyIsNeverRevealed(): void
    {
        $this->storeExtensionConfiguration(
            ['apiKey' => self::API_KEY] + $this->extensionConfigurationBackup
        );
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('effectiveSettings');
        self::assertCount(1, $entries);
        // The whole row, so no column can leak the key unnoticed.
        $serialised = (string)json_encode($entries[0]);

        self::assertStringNotContainsStringIgnoringCase('apiKey', $serialised);
        self::assertStringNotContainsString(self::API_KEY, $serialised);
        // Neither a masked rendering nor a length may appear: both leak shape.
        self::assertStringNotContainsString(str_repeat('*', 4), $serialised);
        self::assertStringNotContainsString((string)strlen(self::API_KEY), $serialised);
    }

    // Diagnostics must never cost a run.
    #[Test]
    public function aListenerThrowingOnTheSummaryDoesNotFailTheImport(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = new class() implements ImportProgressListener {
            public function progressed(ImportProgress $progress): void
            {
            }

            public function settingsResolved(array $settings): void
            {
                throw new RuntimeException('listener blew up', 1787000001);
            }
        };

        $configuration = $this->get(ImportConfigurationRepository::class)->findByUid(1);
        self::assertInstanceOf(ImportConfigurationInterface::class, $configuration);
        $severity = $this->get(Importer::class)->importConfiguration($configuration, $listener);

        self::assertSame('debug', $severity);
    }

    /**
     * @return array<string, string|int>
     */
    private function summaryContext(): array
    {
        $entries = $this->getLogEntriesOfType('effectiveSettings');
        self::assertCount(1, $entries);
        $raw = $entries[0]['context'] ?? '';
        self::assertIsString($raw);
        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded);

        $context = [];
        foreach ($decoded as $name => $value) {
            self::assertTrue(is_string($value) || is_int($value), 'Settings values stay scalar.');
            $context[(string)$name] = $value;
        }

        return $context;
    }
}
