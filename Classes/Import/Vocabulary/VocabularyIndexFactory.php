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
 * Distils several vocabulary documents and merges them into one index.
 */
class VocabularyIndexFactory
{
    public function __construct(
        private readonly VocabularyDistiller $distiller = new VocabularyDistiller()
    ) {
    }

    /**
     * Later documents win on a collision, so the caller controls precedence by
     * ordering. A class redeclared by a second vocabulary is rare but must not
     * produce two entries.
     *
     * @param list<array<mixed>> $documents
     */
    public function fromDocuments(array $documents): VocabularyIndex
    {
        $classes = [];
        foreach ($documents as $document) {
            $classes = array_merge($classes, $this->distiller->distill($document));
        }

        return new VocabularyIndex($classes);
    }
}
