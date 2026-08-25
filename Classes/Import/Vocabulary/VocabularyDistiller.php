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
 * Reduces a whole-vocabulary JSON-LD document to the classes it declares.
 *
 * The two vocabularies we read disagree on every shape: schema.org writes
 * `@type` as a string and labels as plain strings, ThueCat writes both as
 * lists. Absorbing that here keeps the index and its callers shape-agnostic.
 */
class VocabularyDistiller
{
    /**
     * @param array<mixed> $document
     *
     * @return array<string, VocabularyClass> keyed by class id
     */
    public function distill(array $document): array
    {
        $nodes = $document['@graph'] ?? $document;
        if (!is_array($nodes)) {
            return [];
        }

        $classes = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $id = $node['@id'] ?? null;
            if (!is_string($id) || $id === '' || !$this->isClass($node)) {
                continue;
            }

            $classes[$id] = new VocabularyClass(
                $id,
                $this->identifiers($node['rdfs:subClassOf'] ?? null),
                $this->labels($node['rdfs:label'] ?? null)
            );
        }

        return $classes;
    }

    /**
     * A node may declare several types; carrying `rdfs:Class` among them is
     * what makes it a class, so properties and enumeration members drop out.
     *
     * @param array<mixed> $node
     */
    private function isClass(array $node): bool
    {
        $types = $node['@type'] ?? null;

        return in_array('rdfs:Class', is_array($types) ? $types : [$types], true);
    }

    /**
     * `rdfs:subClassOf` arrives as one reference or a list of them. Upstream
     * order is preserved.
     *
     * @param mixed $value
     *
     * @return list<string>
     */
    private function identifiers($value): array
    {
        if ($value === null) {
            return [];
        }

        $references = is_array($value) && array_is_list($value) ? $value : [$value];

        $ids = [];
        foreach ($references as $reference) {
            $id = is_array($reference) ? ($reference['@id'] ?? null) : $reference;
            if (is_string($id) && $id !== '' && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Labels arrive as a plain string, one language object, or a list of them.
     * A plain string carries no language: schema.org writes its labels that
     * way, so it is stored untagged rather than claimed for any language.
     *
     * @param mixed $value
     *
     * @return array<string, string>
     */
    private function labels($value): array
    {
        if ($value === null) {
            return [];
        }

        $entries = is_array($value) && array_is_list($value) ? $value : [$value];

        $labels = [];
        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $labels[VocabularyClass::UNTAGGED] = $entry;
                continue;
            }
            if (!is_array($entry)) {
                continue;
            }

            $text = $entry['@value'] ?? null;
            if (!is_string($text) || $text === '') {
                continue;
            }

            $language = $entry['@language'] ?? null;
            $labels[is_string($language) ? $language : VocabularyClass::UNTAGGED] = $text;
        }

        return $labels;
    }
}
