<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SysCategory;

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
        $repository = $this->createStub(SysCategoryRepository::class);
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
        return $payload->getTranslations()['sys_category'][$identifier] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(DataHandlerPayload $payload, string $key): array
    {
        $row = $payload->getDataMap()['sys_category'][$key] ?? null;
        $this->assertIsArray($row, 'No sys_category row staged under key ' . $key);

        return $row;
    }

    /**
     * One staged column as a string, so a test asserts on a value rather than
     * on `mixed`.
     */
    private function columnOf(DataHandlerPayload $payload, string $key, string $column): string
    {
        $value = $this->rowFor($payload, $key)[$column] ?? null;
        $this->assertTrue(is_string($value) || is_int($value), 'Column "' . $column . '" should be scalar, got ' . get_debug_type($value));

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
        $repository = $this->createStub(SysCategoryRepository::class);
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

        $this->assertSame('77', $underOne, 'The stored record is reused.');
        $this->assertSame($underOne, $underTwo, 'A different parent must not mint a second record.');
        $this->assertSame([], $payload->getDataMap()['sys_category'] ?? []);
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

        $this->assertNull($key, 'A term without a title cannot be created.');
        $this->assertSame([], $payload->getDataMap()['sys_category'] ?? []);
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
        $this->assertNull($key);
        $this->assertSame([], $payload->getDataMap()['sys_category'] ?? []);
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

        $this->assertSame([], $this->translationsFor($payload, $this->types()->stagingKey('type:thuecat:Unknown')));
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
        $this->assertNotContains('thuecat:Unknown', $titles);
        $this->assertNotContains('type:thuecat:Unknown', $titles);
    }

    #[Test]
    public function skippingOneTermLeavesTheOthersAlone(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $skipped = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('thuecat:Unknown', []));
        $kept = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));

        $this->assertNull($skipped);
        $this->assertIsString($kept, 'A term with a title is unaffected by its neighbour.');
    }

    #[Test]
    public function decidesOnceThatATermIsSkipped(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();
        $term = new SysCategoryTerm('thuecat:Unknown', []);

        $this->assertNull($this->provision($payload, $state, $this->types(), $term));
        $this->assertTrue($state->wasSkipped($this->types()->stagingKey('type:thuecat:Unknown')), 'The skip is recorded, so the value is not reconsidered for every record carrying it.');
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

        $this->assertIsString($leaf);
        $this->assertSame($top, $this->columnOf($payload, $leaf, 'parent'), 'A missing title costs its own level only, not every level above it.');
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

        $this->assertIsString($leaf);
        $this->assertSame($top, $this->columnOf($payload, $leaf, 'parent'));
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

        $this->assertIsString($leaf);
        $this->assertSame('100', $this->columnOf($payload, $leaf, 'parent'));
    }

    /**
     * One run can file a term in two trees: the run's state shared between
     * them must not hand one tree's row to the other.
     */
    #[Test]
    public function stagesOneRowPerTreeForTheSameIdentifier(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();
        $term = new SysCategoryTerm('Historisch', ['de' => 'Historisch']);

        $first = $this->provision($payload, $state, new SysCategoryAnchor(200, 30, 'keyword:'), $term);
        $second = $this->provision($payload, $state, new SysCategoryAnchor(300, 30, 'keyword:'), $term);

        $this->assertIsString($first);
        $this->assertIsString($second);
        $this->assertNotSame($first, $second);
        $this->assertSame('200', $this->columnOf($payload, $first, 'parent'));
        $this->assertSame('300', $this->columnOf($payload, $second, 'parent'));
        $this->assertSame('keyword:Historisch', $this->rowFor($payload, $second)['remote_id'] ?? null);
    }

    #[Test]
    public function sharesTheRowBetweenAnchorsOnOneParent(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();
        $term = new SysCategoryTerm('Historisch', ['de' => 'Historisch']);

        $first = $this->provision($payload, $state, new SysCategoryAnchor(200, 30, 'keyword:'), $term);
        $second = $this->provision($payload, $state, new SysCategoryAnchor(200, 30, 'keyword:'), $term);

        $this->assertSame($first, $second);
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

        $translations = $this->translationsFor($payload, $this->types()->stagingKey('type:schema:Museum'));

        $this->assertSame('Museum EN', $translations[1]['title'] ?? null);
        $this->assertSame('Musée', $translations[2]['title'] ?? null);
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

        $translations = $this->translationsFor($payload, $this->types()->stagingKey('type:schema:Museum'));

        // Not the default language's title either: an untranslated row would
        // read as a deliberate choice.
        $this->assertArrayNotHasKey(1, $translations, 'English has no title to stage.');
        $this->assertSame('Musée', $translations[2]['title'] ?? null);
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

        $translations = $this->translationsFor($payload, $this->types()->stagingKey('type:schema:Museum'));

        $this->assertSame([1], array_keys($translations), 'Only the configured language is staged.');
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

        $this->assertSame([], $this->translationsFor($payload, $this->types()->stagingKey('type:schema:Museum')));
    }

    #[Test]
    public function stagesTranslationsForATermReusedFromStorage(): void
    {
        $payload = new DataHandlerPayload();
        $repository = $this->createStub(SysCategoryRepository::class);
        $repository->method('findUidsByRemoteId')->willReturn([77]);
        $repository->method('findParent')->willReturn(100);

        new SysCategoryProvisioner($repository)->provision(
            $payload,
            new SysCategoryProvisioningState(),
            $this->types(),
            new SysCategoryTerm('schema:Museum', ['de' => 'Museum', 'en' => 'Museum EN']),
            [1],
            'de',
            ['en' => 1]
        );

        // A stored term still needs its translation kept current.
        $this->assertSame('Museum EN', $this->translationsFor($payload, $this->types()->stagingKey('type:schema:Museum'))[1]['title'] ?? null);
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

        $this->assertIsString($key);
        $this->assertSame('Museum', $this->columnOf($payload, $key, 'title'));
        $this->assertSame('20', $this->columnOf($payload, $key, 'pid'), "Staged at the anchor's storage pid.");
        $this->assertSame('100', $this->columnOf($payload, $key, 'parent'), 'Hangs off the anchor.');
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

        $this->assertIsString($key);
        $this->assertSame('type:schema:Museum', $this->rowFor($payload, $key)['remote_id'] ?? null);
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

        $this->assertNotSame($typeKey, $keywordKey, 'Sharing the service must not merge the trees.');
        $this->assertIsString($typeKey);
        $this->assertIsString($keywordKey);
        $this->assertSame('20', $this->columnOf($payload, $typeKey, 'pid'));
        $this->assertSame('30', $this->columnOf($payload, $keywordKey, 'pid'));
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

        $this->assertIsString($typeKey);
        $this->assertIsString($keywordKey);
        $this->assertSame('type:Historisch', $this->rowFor($payload, $typeKey)['remote_id'] ?? null);
        $this->assertSame('keyword:Historisch', $this->rowFor($payload, $keywordKey)['remote_id'] ?? null);
    }

    #[Test]
    public function deduplicatesWithinOneConsumersState(): void
    {
        $payload = new DataHandlerPayload();
        $state = new SysCategoryProvisioningState();

        $first = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));
        $second = $this->provision($payload, $state, $this->types(), new SysCategoryTerm('schema:Museum', ['de' => 'Museum']));

        $this->assertSame($first, $second, 'A value seen twice in one run yields one record.');
        $this->assertCount(1, $payload->getDataMap()['sys_category'] ?? []);
    }

    #[Test]
    public function deduplicationDoesNotReachAcrossConsumers(): void
    {
        $payload = new DataHandlerPayload();

        $typeKey = $this->provision($payload, new SysCategoryProvisioningState(), $this->types(), new SysCategoryTerm('Historisch', ['de' => 'Historisch']));
        $keywordKey = $this->provision($payload, new SysCategoryProvisioningState(), $this->keywords(), new SysCategoryTerm('Historisch', ['de' => 'Historisch']));

        $this->assertNotSame($typeKey, $keywordKey);
        $this->assertCount(2, $payload->getDataMap()['sys_category'] ?? []);
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

        $this->assertIsString($childKey);
        $this->assertSame($parentKey, $this->columnOf($payload, $childKey, 'parent'));
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

        $this->assertIsString($key);
        $this->assertSame('100', $this->columnOf($payload, $key, 'parent'));
    }
}
