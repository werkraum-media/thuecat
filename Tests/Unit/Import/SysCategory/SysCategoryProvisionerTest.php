<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SysCategory;

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
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryAnchor;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioner;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioningState;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryTerm;

/**
 * The anchor and identifier prefix are inputs, so two consumers sharing this
 * service must not share the trees it builds.
 */
class SysCategoryProvisionerTest extends TestCase
{
    private function types(): SysCategoryAnchor
    {
        return new SysCategoryAnchor(100, 20, 'type:');
    }

    private function keywords(): SysCategoryAnchor
    {
        return new SysCategoryAnchor(200, 30, 'keyword:');
    }

    /**
     * Answers "already stored?" with nothing, so every provision creates.
     */
    private function provisioner(): SysCategoryProvisioner
    {
        $repository = self::createStub(SysCategoryRepository::class);
        $repository->method('findUidsByRemoteId')->willReturn([]);

        return new SysCategoryProvisioner($repository);
    }

    /**
     * @param array<string, int> $translationLanguages
     */
    private function provision(
        DataHandlerPayload $payload,
        SysCategoryProvisioningState $state,
        SysCategoryAnchor $anchor,
        SysCategoryTerm $term,
        array $translationLanguages = []
    ): ?string {
        return $this->provisioner()->provision(
            $payload,
            $state,
            $anchor,
            $term,
            [1],
            'de',
            $translationLanguages
        );
    }

    /**
     * Translations staged for one identifier, as language uid => field => value.
     *
     * @return array<int, array<string, mixed>>
     */
    private function translationsFor(DataHandlerPayload $payload, string $identifier): array
    {
        $staged = $payload->getTranslations()['sys_category'][$identifier] ?? [];
        self::assertIsArray($staged);

        return $staged;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(DataHandlerPayload $payload, string $key): array
    {
        $row = $payload->getDataMap()['sys_category'][$key] ?? null;
        self::assertIsArray($row, 'No sys_category row staged under key ' . $key);

        return $row;
    }

    /**
     * One staged column as a string, so a test asserts on a value rather than
     * on `mixed`.
     */
    private function columnOf(DataHandlerPayload $payload, string $key, string $column): string
    {
        $value = $this->rowFor($payload, $key)[$column] ?? null;
        self::assertTrue(
            is_string($value) || is_int($value),
            'Column "' . $column . '" should be scalar, got ' . get_debug_type($value)
        );

        return (string)$value;
    }

    // A stored term is matched by identifier, so a term arriving with a
    // different parent moves rather than duplicating. This is what lets the
    // tie-break strategy change later without invalidating plugin
    // configurations that reference category uids.
    #[Test]
    public function reusesAStoredTermWhateverParentItArrivesWith(): void
    {
        $payload = new DataHandlerPayload();
        $repository = self::createStub(SysCategoryRepository::class);
        $repository->method('findUidsByRemoteId')->willReturn([77]);
        $repository->method('findParent')->willReturn(100);
        $provisioner = new SysCategoryProvisioner($repository);

        $underOne = $provisioner->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'schema:BranchOne'),
            [1],
            'de',
            []
        );
        $underTwo = $provisioner->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'schema:BranchTwo'),
            [1],
            'de',
            []
        );

        self::assertSame('77', $underOne, 'The stored record is reused.');
        self::assertSame($underOne, $underTwo, 'A different parent must not mint a second record.');
        self::assertSame([], $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function skipsATermWithNoTitleInAnySource(): void
    {
        $payload = new DataHandlerPayload();

        $key = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('thuecat:Unknown', [])
        );

        self::assertNull($key, 'A term without a title cannot be created.');
        self::assertSame([], $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function skipsATermTitledOnlyInATranslationLanguage(): void
    {
        $payload = new DataHandlerPayload();

        $key = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('thuecat:Unknown', ['en' => 'Only English']),
            ['en' => 1]
        );

        // A translation cannot stand without the record it translates.
        self::assertNull($key);
        self::assertSame([], $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function stagesNoTranslationsForASkippedTerm(): void
    {
        $payload = new DataHandlerPayload();

        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('thuecat:Unknown', ['en' => 'Only English']),
            ['en' => 1]
        );

        self::assertSame([], $this->translationsFor($payload, 'type:thuecat:Unknown'));
    }

    #[Test]
    public function neverTitlesATermAfterItsOwnSourceValue(): void
    {
        $payload = new DataHandlerPayload();

        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('thuecat:Unknown', [])
        );

        $titles = array_column($payload->getDataMap()['sys_category'] ?? [], 'title');
        self::assertNotContains('thuecat:Unknown', $titles);
        self::assertNotContains('type:thuecat:Unknown', $titles);
    }

    #[Test]
    public function skippingOneTermLeavesTheOthersAlone(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $skipped = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:Unknown', []));
        $kept = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));

        self::assertNull($skipped);
        self::assertIsString($kept, 'A term with a title is unaffected by its neighbour.');
    }

    #[Test]
    public function decidesOnceThatATermIsSkipped(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();
        $term = new SysCategoryTerm('thuecat:Unknown', []);

        self::assertNull($this->provision($payload, $state, $this->types(), $term));
        self::assertTrue(
            $state->wasSkipped('type:thuecat:Unknown'),
            'The skip is recorded, so the value is not reconsidered for every record carrying it.'
        );
    }

    #[Test]
    public function childrenOfASkippedTermAttachToTheNearestCreatedAncestor(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        // The middle of the chain has no title in any source.
        $top = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Place', ['de' => 'Ort']));
        $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:Untitled', [], 'schema:Place'));
        $leaf = $this->provision(
            $payload,
            $state,
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'thuecat:Untitled')
        );

        self::assertIsString($leaf);
        self::assertSame(
            $top,
            $this->columnOf($payload, $leaf, 'parent'),
            'A missing title costs its own level only, not every level above it.'
        );
    }

    #[Test]
    public function risesPastTwoAdjacentSkippedTerms(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $top = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Place', ['de' => 'Ort']));
        $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:UntitledOne', [], 'schema:Place'));
        $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:UntitledTwo', [], 'thuecat:UntitledOne'));
        $leaf = $this->provision(
            $payload,
            $state,
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'thuecat:UntitledTwo')
        );

        self::assertIsString($leaf);
        self::assertSame($top, $this->columnOf($payload, $leaf, 'parent'));
    }

    #[Test]
    public function attachesToTheAnchorWhenEveryAncestorWasSkipped(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:Untitled', []));
        $leaf = $this->provision(
            $payload,
            $state,
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'thuecat:Untitled')
        );

        self::assertIsString($leaf);
        self::assertSame('100', $this->columnOf($payload, $leaf, 'parent'));
    }

    #[Test]
    public function stagesATranslationPerConfiguredLanguageThatHasATitle(): void
    {
        $payload = new DataHandlerPayload();

        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'en' => 'Museum EN', 'fr' => 'Musée']),
            ['en' => 1, 'fr' => 2]
        );

        $translations = $this->translationsFor($payload, 'type:schema:Museum');

        self::assertSame('Museum EN', $translations[1]['title'] ?? null);
        self::assertSame('Musée', $translations[2]['title'] ?? null);
    }

    #[Test]
    public function stagesNoTranslationForALanguageWithoutATitle(): void
    {
        $payload = new DataHandlerPayload();

        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'fr' => 'Musée']),
            ['en' => 1, 'fr' => 2]
        );

        $translations = $this->translationsFor($payload, 'type:schema:Museum');

        // Not the default language's title either: an untranslated row would
        // read as a deliberate choice.
        self::assertArrayNotHasKey(1, $translations, 'English has no title to stage.');
        self::assertSame('Musée', $translations[2]['title'] ?? null);
    }

    #[Test]
    public function stagesNothingForALanguageTheSiteDoesNotDefine(): void
    {
        $payload = new DataHandlerPayload();

        // Upstream carries pl; the site configures only en.
        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'en' => 'Museum EN', 'pl' => 'Muzeum']),
            ['en' => 1]
        );

        $translations = $this->translationsFor($payload, 'type:schema:Museum');

        self::assertSame([1], array_keys($translations), 'Only the configured language is staged.');
    }

    #[Test]
    public function stagesNoTranslationsWhenTheSiteDefinesNone(): void
    {
        $payload = new DataHandlerPayload();

        $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'en' => 'Museum EN'])
        );

        self::assertSame([], $this->translationsFor($payload, 'type:schema:Museum'));
    }

    #[Test]
    public function stagesTranslationsForATermReusedFromStorage(): void
    {
        $payload = new DataHandlerPayload();
        $repository = self::createStub(SysCategoryRepository::class);
        $repository->method('findUidsByRemoteId')->willReturn([77]);
        $repository->method('findParent')->willReturn(100);

        (new SysCategoryProvisioner($repository))->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'en' => 'Museum EN']),
            [1],
            'de',
            ['en' => 1]
        );

        // A stored term still needs its translation kept current.
        self::assertSame(
            'Museum EN',
            $this->translationsFor($payload, 'type:schema:Museum')[1]['title'] ?? null
        );
    }

    #[Test]
    public function stagesATermUnderItsAnchor(): void
    {
        $payload = new DataHandlerPayload();

        $key = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'])
        );

        self::assertIsString($key);
        self::assertSame('Museum', $this->columnOf($payload, $key, 'title'));
        self::assertSame('20', $this->columnOf($payload, $key, 'pid'), 'Staged at the anchor\'s storage pid.');
        self::assertSame('100', $this->columnOf($payload, $key, 'parent'), 'Hangs off the anchor.');
    }

    #[Test]
    public function identifiesATermByItsPrefixedSourceValue(): void
    {
        $payload = new DataHandlerPayload();

        $key = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'])
        );

        self::assertIsString($key);
        self::assertSame('type:schema:Museum', $this->rowFor($payload, $key)['remote_id'] ?? null);
    }

    #[Test]
    public function sameTitleUnderTwoAnchorsYieldsTwoRecords(): void
    {
        $payload = new DataHandlerPayload();

        $typeKey = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('thuecat:Historisch', ['de' => 'Historisch'])
        );
        $keywordKey = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->keywords(),
            new SysCategoryTerm('thuecat:Historisch', ['de' => 'Historisch'])
        );

        self::assertNotSame($typeKey, $keywordKey, 'Sharing the service must not merge the trees.');
        self::assertIsString($typeKey);
        self::assertIsString($keywordKey);
        self::assertSame('20', $this->columnOf($payload, $typeKey, 'pid'));
        self::assertSame('30', $this->columnOf($payload, $keywordKey, 'pid'));
    }

    #[Test]
    public function identifierPrefixesKeepTextuallyIdenticalValuesApart(): void
    {
        $payload = new DataHandlerPayload();

        $typeKey = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('Historisch', ['de' => 'Historisch'])
        );
        $keywordKey = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->keywords(),
            new SysCategoryTerm('Historisch', ['de' => 'Historisch'])
        );

        self::assertIsString($typeKey);
        self::assertIsString($keywordKey);
        self::assertSame('type:Historisch', $this->rowFor($payload, $typeKey)['remote_id'] ?? null);
        self::assertSame('keyword:Historisch', $this->rowFor($payload, $keywordKey)['remote_id'] ?? null);
    }

    #[Test]
    public function deduplicatesWithinOneConsumersState(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $first = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));
        $second = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));

        self::assertSame($first, $second, 'A value seen twice in one run yields one record.');
        self::assertCount(1, $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function deduplicationDoesNotReachAcrossConsumers(): void
    {
        $payload = new DataHandlerPayload();

        $typeKey = $this->provision($payload, new SysCategoryProvisioningState(), $this->types(), new SysCategoryTerm('Historisch', ['de' => 'Historisch']));
        $keywordKey = $this->provision($payload, new SysCategoryProvisioningState(), $this->keywords(), new SysCategoryTerm('Historisch', ['de' => 'Historisch']));

        self::assertNotSame($typeKey, $keywordKey);
        self::assertCount(2, $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function nestsBeneathAnAlreadyProvisionedParent(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $parentKey = $this->provision(
            $payload,
            $state,
            $this->types(),
            new SysCategoryTerm('schema:CivicStructure', ['de' => 'Öffentliches Bauwerk'])
        );
        $childKey = $this->provision(
            $payload,
            $state,
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'schema:CivicStructure')
        );

        self::assertIsString($childKey);
        self::assertSame($parentKey, $this->columnOf($payload, $childKey, 'parent'));
    }

    #[Test]
    public function hangsOffTheAnchorWhenTheParentWasNeverProvisioned(): void
    {
        $payload = new DataHandlerPayload();

        $key = $this->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum'], 'schema:NeverSeen')
        );

        self::assertIsString($key);
        self::assertSame('100', $this->columnOf($payload, $key, 'parent'));
    }
}
