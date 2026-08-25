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
 * One distilled class: what it descends from, and what it is called.
 *
 * Labels are keyed by language code. An untagged label is stored under
 * self::UNTAGGED, because schema.org writes plain strings while ThueCat tags
 * every label; the reader decides what an untagged label is worth.
 */
final class VocabularyClass
{
    public const UNTAGGED = '';

    /**
     * @param list<string>          $parents declared parents, in upstream order
     * @param array<string, string> $labels  language code => label
     */
    public function __construct(
        public readonly string $id,
        public readonly array $parents,
        public readonly array $labels
    ) {
    }

    public function label(string $language): ?string
    {
        return $this->labels[$language] ?? null;
    }
}
