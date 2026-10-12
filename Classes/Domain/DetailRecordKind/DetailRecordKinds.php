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

namespace WerkraumMedia\ThueCat\Domain\DetailRecordKind;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Every record kind with its own detail page. Consumers ask here instead of
 * naming a detail table, so a kind added by another package reaches them all.
 */
#[Autoconfigure(public: true)]
final class DetailRecordKinds
{
    /** @var array<string, DetailRecordKind> */
    private array $kinds = [];

    /**
     * @param iterable<DetailRecordKindProvider> $providers
     */
    public function __construct(
        #[AutowireIterator(DetailRecordKindProvider::TAG)]
        iterable $providers,
    ) {
        foreach ($providers as $provider) {
            $kind = $provider->getKind();
            $this->kinds[$kind->identifier] = $kind;
        }
    }

    /** @return list<DetailRecordKind> */
    public function all(): array
    {
        return array_values($this->kinds);
    }

    public function get(string $identifier): DetailRecordKind
    {
        return $this->kinds[$identifier]
            ?? throw new RuntimeException('No detail record kind "' . $identifier . '" is registered.', 1791786566);
    }

    public function forTable(string $table): ?DetailRecordKind
    {
        foreach ($this->kinds as $kind) {
            if ($kind->table === $table) {
                return $kind;
            }
        }

        return null;
    }

    /** The kind's canonical show page in this site; 0 when the site names none. */
    public function showPageUid(DetailRecordKind $kind, Site $site): int
    {
        $pageUid = $site->getSettings()->get($kind->showPageSetting, 0);

        return is_numeric($pageUid) ? (int)$pageUid : 0;
    }
}
