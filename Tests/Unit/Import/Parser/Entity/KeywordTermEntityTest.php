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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Parser\Entity\KeywordTermEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class KeywordTermEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function handlesTermAndTermSetTypes(): void
    {
        $handled = (new KeywordTermEntity())->handlesTypes();

        self::assertContains('schema:DefinedTerm', $handled);
        self::assertContains('schema:DefinedTermSet', $handled);
    }

    // Carries a resolved label for the collector; the sys_category row is
    // created at flush, so the entity itself produces no row data.
    #[Test]
    public function exposesOnlyTheResolvedLabelAsRowData(): void
    {
        $row = $this->parse($this->termNode(), 'de')->toArray();

        self::assertSame('Landeshauptstadt Erfurt', $row['title'] ?? null);
        self::assertArrayNotHasKey('pid', $row);
    }

    #[Test]
    public function takesTitleFromLabelInRequestedLanguage(): void
    {
        $entity = $this->parse($this->termNode(), 'en');

        self::assertSame('State capital Erfurt', $entity->getTitle());
    }

    #[Test]
    public function fallsBackToGermanWhenLanguageMissing(): void
    {
        $entity = $this->parse($this->termNode(), 'nl');

        self::assertSame('Landeshauptstadt Erfurt', $entity->getTitle());
    }

    #[Test]
    public function reportsUnusableWhenNoLabelAtAll(): void
    {
        $entity = $this->parse([
            '@id' => 'https://thuecat.org/resources/151338591378-xzxq',
            '@type' => [],
            'thuecat:keywordOf' => [['@id' => 'https://thuecat.org/resources/114290407966-exnz']],
        ], 'de');

        self::assertFalse($entity->isUsable());
    }

    #[Test]
    public function recordsParentAsKeywordTransient(): void
    {
        $entity = $this->parse($this->termNode(), 'de');

        self::assertSame(
            ['https://thuecat.org/resources/155933862969-mofh'],
            $entity->getTransients()['keywords'] ?? []
        );
    }

    // A set is the top of its chain; nothing above it to walk to.
    #[Test]
    public function recordsNoParentForTopLevelSet(): void
    {
        $entity = $this->parse([
            '@id' => 'https://thuecat.org/resources/155933862969-mofh',
            '@type' => ['schema:DefinedTermSet'],
            'rdfs:label' => [['@language' => 'de', '@value' => 'Landkreise']],
        ], 'de');

        self::assertArrayNotHasKey('keywords', $entity->getTransients());
        self::assertSame('Landkreise', $entity->getTitle());
    }

    // keywordOf lists every object using the term — hundreds of entries on a
    // real one. Following it would fan the import across the catalogue.
    #[Test]
    public function neverFollowsTheReverseIndex(): void
    {
        $entity = $this->parse($this->termNode(), 'de');

        $referenced = array_merge(...array_values($entity->getTransients()));

        self::assertNotContains('https://thuecat.org/resources/043064193523-jcyt', $referenced);
        // Guards the guard: without a transient at all the assertion above
        // would hold vacuously.
        self::assertContains('https://thuecat.org/resources/155933862969-mofh', $referenced);
    }

    /**
     * @return array<string, mixed>
     */
    private function termNode(): array
    {
        return [
            '@id' => 'https://thuecat.org/resources/475728955106-qdcc',
            '@type' => ['schema:DefinedTerm', 'ttgds:Term'],
            'rdfs:label' => [
                ['@language' => 'de', '@value' => 'Landeshauptstadt Erfurt'],
                ['@language' => 'en', '@value' => 'State capital Erfurt'],
            ],
            'schema:inDefinedTermSet' => ['@id' => 'https://thuecat.org/resources/155933862969-mofh'],
            'schema:isPartOf' => ['@id' => 'https://thuecat.org/resources/155933862969-mofh'],
            'thuecat:keywordOf' => [
                ['@id' => 'https://thuecat.org/resources/043064193523-jcyt'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $node
     */
    private function parse(array $node, string $language): KeywordTermEntity
    {
        $entity = new KeywordTermEntity();
        $entity->parse($node, $language, new ParserContext(1));

        return $entity;
    }
}
