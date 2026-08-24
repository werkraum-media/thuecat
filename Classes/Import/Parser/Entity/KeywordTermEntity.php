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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/** Vocabulary node: contributes a category, not a database row. */
class KeywordTermEntity extends AbstractEntity
{
    public const TABLE = '';

    public const PARENT_BUCKET = 'keywords';

    /** Upstream always carries German; other languages may be absent. */
    private const FALLBACK_LANGUAGE = 'de';

    protected string $remote_id = '';

    protected string $title = '';

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->remote_id = $this->getRemoteId($node);

        $label = $node['rdfs:label'] ?? null;
        $this->title = $this->extractValue($label, $language);
        if ($this->title === '' && $language !== self::FALLBACK_LANGUAGE) {
            $this->title = $this->extractValue($label, self::FALLBACK_LANGUAGE);
        }

        foreach ($translationLanguages as $code => $sysLanguageUid) {
            $this->recordTranslation('title', $this->extractValue($label, $code), $sysLanguageUid);
        }

        // inDefinedTermSet and isPartOf agree in all surveyed data; prefer the
        // specific one. A set has neither, which terminates the upward walk.
        $this->recordTransient(
            self::PARENT_BUCKET,
            $node['schema:inDefinedTermSet'] ?? $node['schema:isPartOf'] ?? null
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** False when upstream returned a husk: no label means no usable category. */
    public function isUsable(): bool
    {
        return $this->title !== '';
    }

    /** Null on a term set, which tops its chain and ends the upward walk. */
    public function getParentRemoteId(): ?string
    {
        $parent = $this->getTransients()[self::PARENT_BUCKET][0] ?? null;

        return is_string($parent) ? $parent : null;
    }

    public function handlesTypes(): array
    {
        return ['schema:DefinedTerm', 'schema:DefinedTermSet'];
    }
}
