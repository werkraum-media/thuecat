<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Codappix\Typo3PhpDatasets\TestingFramework;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

/**
 * Base for tests that inspect how an import is configured without running one:
 * anchor resolution, pre-flight validation. They bring their own pages and
 * sites, so no site fixture is provided and each test may write site
 * configuration freely.
 *
 * Tests that actually import extend AbstractImportTestCase instead, which
 * supplies the shared 'example' site and the HTTP faker.
 */
abstract class AbstractImportConfigurationTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use TestingFramework;

    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'extbase',
        'frontend',
        'install',
        'filelist',
        'filemetadata',
    ];

    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat',
        'werkraummedia/events',
    ];

    /** @var array<mixed> */
    private array $extensionConfigurationBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionConfigurationBackup = $this->currentExtensionConfiguration();
        // ExtensionConfiguration::get() repopulates globals from
        // ext_conf_template.txt on a missing path, so a stale anchor has to go
        // before the resolver is asked.
        $this->storeExtensionConfiguration($this->withoutAnchors($this->extensionConfigurationBackup));
    }

    // The suite runs with backupGlobals="false", so an instance-wide fallback
    // set here would otherwise persist into every later test.
    protected function tearDown(): void
    {
        $this->storeExtensionConfiguration($this->extensionConfigurationBackup);
        parent::tearDown();
    }

    /**
     * @param array<string, string> $values keyed by extension configuration key
     */
    protected function writeExtensionConfiguration(array $values): void
    {
        $this->storeExtensionConfiguration($values + $this->withoutAnchors($this->extensionConfigurationBackup));
    }

    /**
     * @param array<mixed> $configuration
     *
     * @return array<mixed>
     */
    private function withoutAnchors(array $configuration): array
    {
        foreach (CategoryAnchorSetting::cases() as $setting) {
            unset($configuration[$setting->extensionConfigurationKey()]);
        }

        return $configuration;
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

    /**
     * @param array<string, mixed> $settings nested, as in config.yaml
     */
    protected function writeSiteSettings(array $settings, string $identifier, int $rootPageId): void
    {
        $this->get(SiteWriter::class)->write($identifier, [
            'rootPageId' => $rootPageId,
            // Path base, not a host derived from $identifier: identifiers may
            // contain underscores, which are not valid in a hostname.
            'base' => '/' . $identifier . '/',
            'languages' => [
                [
                    'title' => 'Deutsch',
                    'enabled' => true,
                    'base' => '/',
                    'typo3Language' => 'de',
                    'locale' => 'de_DE.UTF-8',
                    'navigationTitle' => 'Deutsch',
                    'flag' => 'de',
                    'languageId' => 0,
                ],
            ],
            'settings' => $settings,
        ]);
    }
}
