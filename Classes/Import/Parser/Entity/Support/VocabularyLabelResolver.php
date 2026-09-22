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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

use Throwable;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyProvider;

/**
 * Turns an enum value into the term a reader recognises.
 *
 * ThueCat publishes enum members as CURIEs pointing at ontology classes that
 * carry their own labels, so `thuecat:Germany` read in German is `Deutschland`.
 *
 * A value that is no CURIE is returned unchanged. When the vocabulary cannot
 * answer — no such class, no label in the requested language, or an upstream
 * failure — the bare member name stands in, which is what the import stores for
 * every other enum.
 */
class VocabularyLabelResolver
{
    public function __construct(
        private readonly VocabularyProvider $vocabularyProvider,
        private readonly CurieExpander $curieExpander = new CurieExpander()
    ) {
    }

    public function resolve(string $value, string $language, ?string $apiKey = null): string
    {
        if ($value === '') {
            return '';
        }

        $uri = $this->curieExpander->expand($value);
        if ($uri === null) {
            return $value;
        }

        return $this->label($uri, $language, $apiKey) ?? $this->stripPrefix($value);
    }

    private function label(string $uri, string $language, ?string $apiKey): ?string
    {
        try {
            $label = $this->vocabularyProvider->index($apiKey)->get($uri)?->label($language);
        } catch (Throwable) {
            // An enum value must never fail an import; the bare name still reads.
            return null;
        }

        return $label === '' ? null : $label;
    }

    private function stripPrefix(string $value): string
    {
        $colon = strpos($value, ':');
        return $colon === false ? $value : substr($value, $colon + 1);
    }
}
