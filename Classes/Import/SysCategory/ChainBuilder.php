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

use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * The chain of classes to provision for one type, ordered from the topmost
 * ancestor down to the type itself, so each level exists before the level that
 * hangs beneath it.
 *
 * Classes carrying no editorial meaning are cut off: everything imported is a
 * `Thing` and nearly everything is a `Place`, so a category for either would
 * sit uselessly above every tree.
 *
 * Where a class declares several parents, the chain follows one of them. Which
 * one is decided in two steps: a parent that is merely an ancestor of another
 * parent restates the chain rather than branching it and is dropped, and a
 * genuine branch is settled by a strategy the caller chooses.
 */
class ChainBuilder
{
    /**
     * Classes every imported record belongs to, which therefore say nothing
     * about any of them.
     */
    public const CUT_OFF = [
        'schema:Thing',
        'schema:Place',
    ];

    /**
     * Ancestors first, the type itself last. Empty where the index knows
     * nothing of the type.
     *
     * @param callable(string, string, list<string>): void|null $onBranch called
     *        for each genuine branch with the class, the parent followed and
     *        the ones passed over
     *
     * @return list<string>
     */
    public function build(
        VocabularyIndex $index,
        string $type,
        ParentStrategy $strategy,
        ?callable $onBranch = null
    ): array {
        if (!$index->has($type)) {
            return [];
        }

        $chain = [];
        $current = $type;
        $seen = [];

        while ($current !== null && !isset($seen[$current])) {
            $seen[$current] = true;
            if (!$this->isCutOff($current)) {
                $chain[] = $current;
            }

            $current = $this->parentOf($index, $current, $strategy, $onBranch);
        }

        return array_reverse($chain);
    }

    /**
     * The one parent to follow, or null where the class has none left to
     * follow. A parent below the cutoff still ends the walk: nothing above it
     * carries meaning either.
     */
    protected function parentOf(
        VocabularyIndex $index,
        string $class,
        ParentStrategy $strategy,
        ?callable $onBranch = null
    ): ?string {
        $candidates = $this->reduce($index, $index->parents($class));

        if ($candidates === []) {
            return null;
        }
        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $chosen = $strategy->choose($index, $class, $candidates);
        if ($onBranch !== null) {
            $onBranch($class, $chosen, array_values(array_filter(
                $candidates,
                static fn (string $candidate): bool => $candidate !== $chosen
            )));
        }

        return $chosen;
    }

    /**
     * Drops any parent that is an ancestor of another parent: it restates the
     * chain rather than branching it, and keeps its own level further up
     * anyway.
     *
     * @param list<string> $parents
     *
     * @return list<string>
     */
    protected function reduce(VocabularyIndex $index, array $parents): array
    {
        $reduced = [];
        foreach ($parents as $parent) {
            foreach ($parents as $other) {
                if ($other !== $parent && in_array($parent, $index->ancestors($other), true)) {
                    continue 2;
                }
            }
            $reduced[] = $parent;
        }

        return $reduced;
    }

    protected function isCutOff(string $class): bool
    {
        return in_array($class, self::CUT_OFF, true);
    }
}
