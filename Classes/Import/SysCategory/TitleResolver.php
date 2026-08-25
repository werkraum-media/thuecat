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

use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;

/**
 * Decides what a source value is called in each language the site configures.
 *
 * Upstream is asked first, per language. Where it is silent for the default
 * language the fallback map answers — and that it was asked is recorded,
 * because the import report exists to name the titles a person still maintains.
 * A translation upstream lacks is not the map's business: it holds
 * default-language titles only.
 *
 * One upstream label needs its own rule: a label carrying no language is
 * English in practice, not a title for whatever language happens to be the
 * default. It answers English alone, so a class labelled only that way takes
 * its English title from upstream and everything else from the map.
 */
class TitleResolver
{
    protected const ENGLISH = 'en';

    /**
     * @param list<string>          $languages configured language codes,
     *                                         default language included
     * @param array<string, string> $fallback  source value => title, in the
     *                                         default language
     */
    public function resolve(
        string $sourceValue,
        ?VocabularyClass $class,
        array $languages,
        array $fallback,
        string $defaultLanguage
    ): TitleResolution {
        $titles = [];
        $usedFallback = false;

        foreach ($languages as $language) {
            $title = $this->fromUpstream($class, $language);
            if ($title === null) {
                // Only the default language counts: the map holds nothing but
                // default-language titles, so a translation it cannot supply
                // says nothing about whether the map is carrying this term.
                $usedFallback = $usedFallback || $language === $defaultLanguage;
                $title = $this->fromFallback($fallback, $sourceValue, $language, $defaultLanguage);
            }

            if ($title !== null) {
                $titles[$language] = $title;
            }
        }

        return new TitleResolution($titles, $usedFallback);
    }

    /**
     * A language-tagged label answers its own language. An untagged one answers
     * English and nothing else: it is English in practice, not a title for
     * whichever language happens to be the default.
     */
    protected function fromUpstream(?VocabularyClass $class, string $language): ?string
    {
        if ($class === null) {
            return null;
        }

        $tagged = $class->label($language);
        if ($tagged !== null && $tagged !== '') {
            return $tagged;
        }

        if ($language !== self::ENGLISH) {
            return null;
        }

        $untagged = $class->label(VocabularyClass::UNTAGGED);

        return ($untagged === null || $untagged === '') ? null : $untagged;
    }

    /**
     * The map holds default-language titles only, so it answers that language
     * alone; a translation it cannot supply is simply not staged.
     *
     * @param array<string, string> $fallback
     */
    protected function fromFallback(
        array $fallback,
        string $sourceValue,
        string $language,
        string $defaultLanguage
    ): ?string {
        if ($language !== $defaultLanguage) {
            return null;
        }

        $title = $fallback[$sourceValue] ?? null;

        return ($title === null || $title === '') ? null : $title;
    }
}
