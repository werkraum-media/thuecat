<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Vocabulary;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyDistiller;

/**
 * Node shapes below are taken verbatim from the published vocabularies:
 * schema.org writes `@type` and labels as plain strings, ThueCat writes both
 * as lists, and both write `rdfs:subClassOf` as either a dict or a list.
 */
class VocabularyDistillerTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $nodes
     *
     * @return array<string, VocabularyClass>
     */
    private function distill(array $nodes): array
    {
        return (new VocabularyDistiller())->distill(['@graph' => $nodes]);
    }

    /**
     * Asserts the class was distilled at all, then hands it over so the caller
     * can assert what it holds.
     *
     * @param array<string, VocabularyClass> $classes
     */
    private function assertDistilled(array $classes, string $id): VocabularyClass
    {
        self::assertArrayHasKey($id, $classes);

        return $classes[$id];
    }

    #[Test]
    public function extractsSingleParentFromSchemaOrgShape(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'schema:Museum',
                '@type' => 'rdfs:Class',
                'rdfs:label' => 'Museum',
                'rdfs:subClassOf' => ['@id' => 'schema:CivicStructure'],
            ],
        ]);

        self::assertSame(
            ['schema:CivicStructure'],
            $this->assertDistilled($classes, 'schema:Museum')->parents
        );
    }

    #[Test]
    public function extractsSeveralParentsKeepingUpstreamOrder(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'thuecat:Archive',
                '@type' => ['rdfs:Class'],
                'rdfs:subClassOf' => [
                    ['@id' => 'schema:CivicStructure'],
                    ['@id' => 'schema:Museum'],
                ],
            ],
        ]);

        self::assertSame(
            ['schema:CivicStructure', 'schema:Museum'],
            $this->assertDistilled($classes, 'thuecat:Archive')->parents
        );
    }

    #[Test]
    public function classWithoutParentsIsStillDistilled(): void
    {
        $classes = $this->distill([
            ['@id' => 'schema:Thing', '@type' => 'rdfs:Class', 'rdfs:label' => 'Thing'],
        ]);

        self::assertSame([], $this->assertDistilled($classes, 'schema:Thing')->parents);
    }

    #[Test]
    public function extractsLabelsPerLanguageFromListShape(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'thuecat:BrassMusic',
                '@type' => ['rdfs:Class'],
                'rdfs:label' => [
                    ['@language' => 'de', '@value' => 'Blasmusik'],
                    ['@language' => 'en', '@value' => 'Brass music'],
                    ['@language' => 'pl', '@value' => 'Muzyka dęta'],
                ],
            ],
        ]);

        $class = $this->assertDistilled($classes, 'thuecat:BrassMusic');

        self::assertSame('Blasmusik', $class->label('de'));
        self::assertSame('Brass music', $class->label('en'));
        self::assertSame('Muzyka dęta', $class->label('pl'));
    }

    #[Test]
    public function extractsLabelFromSingleDictShape(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'thuecat:AlpineCuisineEnumMem',
                '@type' => ['rdfs:Class'],
                'rdfs:label' => ['@language' => 'pl', '@value' => 'Kuchnia alpejska'],
            ],
        ]);

        self::assertSame(
            'Kuchnia alpejska',
            $this->assertDistilled($classes, 'thuecat:AlpineCuisineEnumMem')->label('pl')
        );
    }

    #[Test]
    public function keepsUntaggedLabelUnderTheUntaggedKey(): void
    {
        $classes = $this->distill([
            ['@id' => 'schema:Bridge', '@type' => 'rdfs:Class', 'rdfs:label' => 'Bridge'],
        ]);

        $class = $this->assertDistilled($classes, 'schema:Bridge');

        self::assertSame('Bridge', $class->label(VocabularyClass::UNTAGGED));
        self::assertNull($class->label('de'), 'An untagged label must not be claimed for a language.');
    }

    #[Test]
    public function classWithoutLabelIsStillDistilled(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'thuecat:Nameless',
                '@type' => ['rdfs:Class'],
                'rdfs:subClassOf' => ['@id' => 'schema:Place'],
            ],
        ]);

        self::assertSame([], $this->assertDistilled($classes, 'thuecat:Nameless')->labels);
    }

    #[Test]
    public function keepsClassesDeclaringSeveralTypes(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'schema:ExerciseAction',
                '@type' => ['rdfs:Class', 'rdfs:Resource', 'schema:Thing', 'schema:Class'],
                'rdfs:label' => 'ExerciseAction',
            ],
        ]);

        self::assertArrayHasKey('schema:ExerciseAction', $classes);
    }

    #[Test]
    public function ignoresNodesThatAreNotClasses(): void
    {
        $classes = $this->distill([
            [
                '@id' => 'schema:addressLocality',
                '@type' => 'rdf:Property',
                'rdfs:label' => 'addressLocality',
            ],
            [
                '@id' => 'thuecat:SomeEnumMember',
                '@type' => ['thuecat:ServesCuisine'],
                'rdfs:label' => 'irrelevant',
            ],
            ['@id' => 'schema:Museum', '@type' => 'rdfs:Class', 'rdfs:label' => 'Museum'],
        ]);

        self::assertSame(['schema:Museum'], array_keys($classes));
    }

    #[Test]
    public function acceptsADocumentWithoutAGraphWrapper(): void
    {
        $classes = (new VocabularyDistiller())->distill([
            ['@id' => 'schema:Museum', '@type' => 'rdfs:Class', 'rdfs:label' => 'Museum'],
        ]);

        self::assertArrayHasKey('schema:Museum', $classes);
    }

    #[Test]
    public function ignoresNodesWithoutAnIdentifier(): void
    {
        $classes = $this->distill([
            ['@type' => 'rdfs:Class', 'rdfs:label' => 'Anonymous'],
            ['@id' => 'schema:Museum', '@type' => 'rdfs:Class', 'rdfs:label' => 'Museum'],
        ]);

        self::assertSame(['schema:Museum'], array_keys($classes));
    }
}
