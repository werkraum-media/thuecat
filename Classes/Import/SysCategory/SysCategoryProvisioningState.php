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

namespace WerkraumMedia\ThueCat\Import\SysCategory;

/**
 * One consumer's provisioning bookkeeping for a run: which source values it has
 * already staged, and under which datamap key.
 *
 * Held per consumer rather than per service, so two fields provisioning the same
 * source value each get their own record. Run-scoped because `resolve()` runs
 * once per root URL and a value recurring across roots must yield one record.
 */
final class SysCategoryProvisioningState
{
    /**
     * Bound to a caller's own map where one already exists, so a consumer that
     * keeps its keys elsewhere — and promotes them there — stays the owner of
     * them.
     *
     * @param array<string, string> $keyByIdentifier prefixed identifier => datamap key
     */
    public function __construct(
        private array &$keyByIdentifier = []
    ) {
    }

    /**
     * Prefixed identifier => the parent's *source value*, so a term below a
     * skipped one can keep climbing to the nearest level that exists.
     *
     * @var array<string, string|null>
     */
    private array $skipped = [];

    public function keyFor(string $identifier): ?string
    {
        return $this->keyByIdentifier[$identifier] ?? null;
    }

    public function remember(string $identifier, string $key): void
    {
        $this->keyByIdentifier[$identifier] = $key;
    }

    public function markSkipped(string $identifier, ?string $parentValue = null): void
    {
        $this->skipped[$identifier] = $parentValue;
    }

    public function wasSkipped(string $identifier): bool
    {
        return array_key_exists($identifier, $this->skipped);
    }

    /**
     * The source value of what a skipped term would have hung beneath, or null
     * where it would have hung off the anchor.
     */
    public function parentOfSkipped(string $identifier): ?string
    {
        return $this->skipped[$identifier] ?? null;
    }

    /**
     * NEW… keys become real uids once DataHandler has run; later roots must see
     * the uid, not a placeholder that no longer means anything.
     *
     * @param array<string, int|string> $substNEWwithIDs
     */
    public function promoteNewKeys(array $substNEWwithIDs): void
    {
        foreach ($this->keyByIdentifier as $identifier => $key) {
            if (isset($substNEWwithIDs[$key])) {
                $this->keyByIdentifier[$identifier] = (string)$substNEWwithIDs[$key];
            }
        }
    }
}
