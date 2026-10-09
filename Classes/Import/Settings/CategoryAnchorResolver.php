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

namespace WerkraumMedia\ThueCat\Import\Settings;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Resolves the sys_category anchors of a record kind: the site decides first,
 * the instance-wide extension configuration second. An anchor no level supplies
 * is 0, which switches its kind's mapping off.
 *
 * Every level is read under a scope's own name. A top-level kind reads its own
 * scope and falls back to the default one; every other kind reads the default
 * scope only, so records shared between imports agree on their tree.
 *
 * Each setting walks the levels on its own, so the two halves of a kind's pair
 * may come from different levels.
 */
#[Autoconfigure(public: true)]
class CategoryAnchorResolver
{
    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly AnchorScopeRegistry $scopes,
    ) {
    }

    /**
     * A record kind's anchors for one kind: its own scope if it is a top-level
     * kind, then the default scope. The first scope supplying either setting
     * answers for the whole pair: completing half a pair from another scope
     * would pair one tree's parent with another's folder.
     *
     * Depends on table and site only, so an import, a frontend filter and a
     * backend form resolve the same tree.
     */
    public function resolvePair(Site $site, string $table, AnchorKind $kind): AnchorPair
    {
        foreach ($this->scopeChain($table) as $scope) {
            $pair = $this->resolveInScope($site, $scope, $kind);
            if ($pair->isSet()) {
                return $pair;
            }
        }

        return new AnchorPair();
    }

    /**
     * One scope's pair without falling back, as validation needs it: a broken
     * scope must be seen even where another scope would stand in for it.
     */
    public function resolveInScope(Site $site, AnchorScope $scope, AnchorKind $kind): AnchorPair
    {
        return new AnchorPair(
            $this->resolve($kind->parentSetting(), $site, $scope),
            $this->resolve($kind->storagePidSetting(), $site, $scope),
            $scope
        );
    }

    /**
     * Every scope that can supply anchors: the top-level kinds' and the
     * default one relation kinds fall back to.
     *
     * @return list<AnchorScope>
     */
    public function scopes(): array
    {
        $scopes = [];
        foreach ([...$this->scopes->scopes(), AnchorScope::default()] as $scope) {
            $scopes[$scope->value] ??= $scope;
        }

        return array_values($scopes);
    }

    /**
     * @return list<AnchorScope>
     */
    protected function scopeChain(string $table): array
    {
        $chain = [];
        foreach ([$this->scopes->forTable($table), AnchorScope::default()] as $scope) {
            if ($scope !== null) {
                $chain[$scope->value] ??= $scope;
            }
        }

        return array_values($chain);
    }

    public function resolve(CategoryAnchorSetting $setting, Site $site, AnchorScope $scope): int
    {
        return $this->asSetValue($site->getSettings()->get($setting->settingsPath($scope)))
            ?? $this->fromExtensionConfiguration($setting, $scope)
            ?? 0;
    }

    /**
     * Both exceptions mean "nothing set at this level": the extension has no
     * configuration at all, or none carrying these keys.
     */
    protected function fromExtensionConfiguration(CategoryAnchorSetting $setting, AnchorScope $scope): ?int
    {
        try {
            $value = $this->extensionConfiguration->get('thuecat', $setting->extensionConfigurationKey($scope));
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
    protected function asSetValue($value): ?int
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }
}
