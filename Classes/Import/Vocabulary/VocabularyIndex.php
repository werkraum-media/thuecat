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

namespace WerkraumMedia\ThueCat\Import\Vocabulary;

/**
 * The distilled classes of every vocabulary, merged into one lookup.
 *
 * Merging is what makes a chain resolvable: ThueCat extends schema.org, so
 * `thuecat:BrassMusic` declares `schema:MusicEvent` as its parent and a climb
 * crosses the boundary without knowing it exists.
 */
final class VocabularyIndex
{
    /**
     * @param array<string, VocabularyClass> $classes
     */
    public function __construct(
        private readonly array $classes = []
    ) {
    }

    /**
     * @return array<string, VocabularyClass>
     */
    public function all(): array
    {
        return $this->classes;
    }

    public function has(string $id): bool
    {
        return isset($this->classes[$id]);
    }

    public function get(string $id): ?VocabularyClass
    {
        return $this->classes[$id] ?? null;
    }

    /**
     * Declared parents of one class, in upstream order.
     *
     * @return list<string>
     */
    public function parents(string $id): array
    {
        return $this->classes[$id]->parents ?? [];
    }

    /**
     * Every ancestor reachable from the class, breadth-first, excluding the
     * class itself. Cycles terminate: a class already seen is never requeued.
     *
     * An undeclared parent is reported and then not descended through — it is
     * as far as that branch goes.
     *
     * @return list<string>
     */
    public function ancestors(string $id): array
    {
        // $id is deliberately not pre-seeded: where upstream declares a cycle
        // the class really is its own ancestor
        $ancestors = [];
        $seen = [];
        $queue = $this->parents($id);

        while ($queue !== []) {
            $current = array_shift($queue);
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            $ancestors[] = $current;

            foreach ($this->parents($current) as $parent) {
                $queue[] = $parent;
            }
        }

        return $ancestors;
    }

    /**
     * References named as a parent by some class but declared by none. A
     * healthy pair of vocabularies leaves this empty.
     *
     * @return list<string>
     */
    public function danglingReferences(): array
    {
        $dangling = [];
        foreach ($this->classes as $class) {
            foreach ($class->parents as $parent) {
                if (!isset($this->classes[$parent]) && !in_array($parent, $dangling, true)) {
                    $dangling[] = $parent;
                }
            }
        }

        return $dangling;
    }
}
