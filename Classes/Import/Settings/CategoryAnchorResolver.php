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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;

/**
 * Resolves a sys_category anchor for one import: the site owning the import's
 * storagePid decides first, the instance-wide extension configuration second.
 * An anchor no level supplies is 0, which switches its kind's mapping off.
 *
 * Each setting walks the chain on its own, so the two halves of a kind's pair
 * may come from different levels.
 */
#[Autoconfigure(public: true)]
class CategoryAnchorResolver
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly SiteFinder $siteFinder
    ) {
    }

    /**
     * @throws SiteNotFoundException storagePid belongs to no site
     */
    public function resolveFor(ImportConfigurationInterface $configuration): CategoryAnchors
    {
        $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());

        return new CategoryAnchors(
            $this->resolve(CategoryAnchorSetting::CategoryParent, $site),
            $this->resolve(CategoryAnchorSetting::CategoryStoragePid, $site),
            $this->resolve(CategoryAnchorSetting::KeywordParent, $site),
            $this->resolve(CategoryAnchorSetting::KeywordStoragePid, $site),
        );
    }

    public function resolve(CategoryAnchorSetting $setting, Site $site): int
    {
        return $this->asSetValue($site->getSettings()->get($setting->settingsPath()))
            ?? $this->fromExtensionConfiguration($setting)
            ?? 0;
    }

    /**
     * Both exceptions mean "nothing set at this level": the extension has no
     * configuration at all, or none carrying these keys.
     */
    private function fromExtensionConfiguration(CategoryAnchorSetting $setting): ?int
    {
        try {
            $value = $this->extensionConfiguration->get('thuecat', $setting->extensionConfigurationKey());
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            return null;
        }

        return $this->asSetValue($value);
    }

    /**
     * A page or category uid is always positive, so anything else means "not
     * set at this level, keep walking".
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
