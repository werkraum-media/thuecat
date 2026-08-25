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
 * Follows the parent standing deepest in the vocabulary, which is the one
 * saying most about the class: a branch reaching further up passed through more
 * classifications on its way.
 *
 * Ties are settled by the order upstream declares its parents, so the choice
 * holds across runs whatever the vocabulary's internal ordering.
 *
 * This is a provisional default. Which parent serves a record kind best is a
 * question for real data, and swapping this out re-parents categories without
 * changing their identity.
 */
class LongestChainStrategy implements ParentStrategy
{
    public function choose(VocabularyIndex $index, string $class, array $candidates): string
    {
        $chosen = $candidates[0];
        $deepest = count($index->ancestors($chosen));

        foreach ($candidates as $candidate) {
            $depth = count($index->ancestors($candidate));
            if ($depth > $deepest) {
                $chosen = $candidate;
                $deepest = $depth;
            }
        }

        return $chosen;
    }

    public function name(): string
    {
        return 'longestChain';
    }
}
