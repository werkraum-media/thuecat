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
 * Picks one parent where a class genuinely declares several, so the created
 * structure is a tree rather than a graph.
 *
 * Which parent serves a record kind best depends on what its categories are
 * for, so the choice is a strategy rather than a rule. An implementation MUST
 * be deterministic: the same class must always yield the same parent, or a
 * re-import re-parents categories for no reason.
 *
 * Only reached for a genuine branch. A parent that is an ancestor of another
 * parent is dropped before this, being a restatement of the chain rather than
 * a fork in it.
 */
interface ParentStrategy
{
    /**
     * @param list<string> $candidates competing parents, in the order upstream
     *                                 declares them, never fewer than two
     */
    public function choose(VocabularyIndex $index, string $class, array $candidates): string;

    /** Names the strategy in the import report, so a choice can be traced. */
    public function name(): string;
}
