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

namespace WerkraumMedia\ThueCat\Import\Settings;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * One resolver for every tunable's fallback chain; the older credential and
 * host fallbacks were open-coded and drifted apart.
 */
class ImportSettings
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * @param mixed $configurationValue The editor's explicit choice from the
     *        import configuration; empty or 0 means "not set".
     */
    public function resolve(ImportSetting $setting, $configurationValue): int
    {
        return $this->asSetValue($configurationValue)
            ?? $this->fromExtensionConfiguration($setting)
            ?? $setting->default();
    }

    /**
     * Both exceptions mean "nothing set at this level" and are not error cases:
     * the extension has no configuration at all, or it has one that predates
     * these keys — the latter being the upgrade path for every existing
     * installation. get() never validates, so a malformed value arrives as a
     * string and is settled by asSetValue() instead.
     */
    private function fromExtensionConfiguration(ImportSetting $setting): ?int
    {
        try {
            $value = $this->extensionConfiguration->get('thuecat', $setting->value);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            return null;
        }

        return is_scalar($value) ? $this->asSetValue($value) : null;
    }

    /**
     * 0 is Guzzle's "unlimited" and also what an empty flexform number field
     * yields. Unlimited is the behaviour this change removes, so 0 means "not
     * set, keep walking the chain" at every level.
     *
     * @param mixed $value
     */
    private function asSetValue($value): ?int
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }
}
