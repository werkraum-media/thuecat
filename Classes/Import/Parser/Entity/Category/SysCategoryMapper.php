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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Category;

/**
 * Maps source values (e.g. an event @type) to sys_category titles. Mechanics
 * live here; each subclass supplies only its data (title map + ignored values)
 * and its kind/prefix.
 */
abstract class SysCategoryMapper
{
    /**
     * Source value → sys_category title.
     *
     * @return array<string, string>
     */
    abstract protected function titleMap(): array;

    /**
     * Source values consciously excluded from the report (no category meaning).
     *
     * @return list<string>
     */
    abstract protected function ignoredValues(): array;

    /**
     * Report 'kind', distinguishing this mapper's entries from others'.
     */
    abstract public function kind(): string;

    /**
     * Marker prefixed onto remote_ids so values from different sources never collide.
     */
    abstract public function sourcePrefix(): string;

    public function prefixed(string $value): string
    {
        return $this->sourcePrefix() . $value;
    }

    public function titleFor(string $value): ?string
    {
        return $this->titleMap()[$value] ?? null;
    }

    /**
     * Mapped categories only, de-duplicated, first-seen order.
     *
     * @param list<string> $values
     *
     * @return list<array{remoteId: string, title: string}>
     */
    public function categoriesFor(array $values): array
    {
        $categories = [];
        $seen = [];
        foreach ($values as $value) {
            $title = $this->titleFor($value);
            if ($title === null || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $categories[] = ['remoteId' => $value, 'title' => $title];
        }
        return $categories;
    }

    /**
     * Classify values into matched and unmatched; ignored values excluded.
     *
     * @param list<string> $values
     *
     * @return array{matched: array<string, string>, unmatched: list<string>}
     */
    public function reportMatchStatus(array $values): array
    {
        $matched = [];
        $unmatched = [];
        foreach ($values as $value) {
            if (in_array($value, $this->ignoredValues(), true)) {
                continue;
            }
            $title = $this->titleFor($value);
            if ($title !== null) {
                $matched[$value] = $title;
            } elseif (!in_array($value, $unmatched, true)) {
                $unmatched[] = $value;
            }
        }
        return ['matched' => $matched, 'unmatched' => $unmatched];
    }
}
