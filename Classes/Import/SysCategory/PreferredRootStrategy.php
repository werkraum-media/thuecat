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

use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * Follows the branch that reaches one of the roots this kind of record belongs
 * under, taking the roots in order of preference.
 *
 * Upstream models a class by what it resembles, not by what an import is for: a
 * theatre venue is a local business as much as it is a tourist attraction, and
 * only the importing side knows which of those a reader came for. The preferred
 * roots express that.
 *
 * Where several candidates reach the same root, the first upstream declares
 * wins, which favours the nearer and more specific parent.
 *
 * A branch reaching no preferred root is handed to the fallback strategy and
 * logged: no rule here fits it, so a person has to look.
 */
class PreferredRootStrategy implements ParentStrategy
{
    /**
     * @param list<string> $preferredRoots most preferred first
     */
    public function __construct(
        protected readonly string $name,
        protected readonly array $preferredRoots,
        protected readonly ParentStrategy $fallback,
        protected readonly ImportLogger $importLogger
    ) {
    }

    public function choose(VocabularyIndex $index, string $class, array $candidates): string
    {
        foreach ($this->preferredRoots as $root) {
            foreach ($candidates as $candidate) {
                if ($candidate === $root || in_array($root, $index->ancestors($candidate), true)) {
                    return $candidate;
                }
            }
        }

        $chosen = $this->fallback->choose($index, $class, $candidates);
        $this->importLogger->recordUnpreferredParent(
            $class,
            $chosen,
            $candidates,
            $this->preferredRoots
        );

        return $chosen;
    }

    public function name(): string
    {
        return $this->name;
    }
}
