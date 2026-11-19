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

namespace WerkraumMedia\ThueCat\Removal;

/**
 * Which roots a removal covers: the roots of one table within a scope, or,
 * to revert an import, the roots of every table within a site whose origin
 * is one import configuration.
 */
final readonly class Selection
{
    /**
     * @param string $rootTable empty when the selection covers every root table
     * @param int $scopePage the folder's uid, or the site's root page uid
     */
    protected function __construct(
        public string $rootTable,
        public Scope $scope,
        public int $scopePage,
        public int $originConfigurationUid,
    ) {
    }

    public static function ofKind(string $rootTable, Scope $scope, int $scopePage = 0): self
    {
        return new self($rootTable, $scope, $scopePage, 0);
    }

    public static function ofOrigin(int $siteRootPage, int $originConfigurationUid): self
    {
        return new self('', Scope::Site, $siteRootPage, $originConfigurationUid);
    }

    /**
     * @param array<mixed> $parameters as built by toParameters()
     */
    public static function fromParameters(array $parameters): ?self
    {
        $page = $parameters['page'] ?? null;
        if (!is_numeric($page)) {
            return null;
        }

        if (array_key_exists('origin', $parameters)) {
            $origin = $parameters['origin'];
            return is_numeric($origin) && (int)$origin > 0 ? self::ofOrigin((int)$page, (int)$origin) : null;
        }

        $table = $parameters['table'] ?? null;
        $scope = Scope::tryFrom(is_string($parameters['scope'] ?? null) ? $parameters['scope'] : '');
        if (!in_array($table, RootTables::ALL, true) || !$scope instanceof Scope) {
            return null;
        }

        return self::ofKind($table, $scope, (int)$page);
    }

    /**
     * @return array<string, string|int>
     */
    public function toParameters(): array
    {
        if ($this->originConfigurationUid > 0) {
            return ['origin' => $this->originConfigurationUid, 'page' => $this->scopePage];
        }

        return ['table' => $this->rootTable, 'scope' => $this->scope->value, 'page' => $this->scopePage];
    }

    /**
     * @return list<string>
     */
    public function rootTables(): array
    {
        return $this->rootTable === '' ? RootTables::ALL : [$this->rootTable];
    }
}
