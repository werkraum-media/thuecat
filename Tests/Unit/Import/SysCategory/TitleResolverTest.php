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
use WerkraumMedia\ThueCat\Import\SysCategory\TitleResolution;
use WerkraumMedia\ThueCat\Import\SysCategory\TitleResolver;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;

/**
 * The two vocabularies label differently: ThueCat tags every label, schema.org
 * writes plain untagged strings that are English in practice. The rules below
 * are what lets one class draw its German from the map and its English from
 * upstream.
 */
class TitleResolverTest extends TestCase
{
    private const FALLBACK = [
        'schema:Bridge' => 'Brücke',
        'thuecat:BeerBar' => 'Bierbar',
    ];

    /**
     * @param array<string, string> $labels
     */
    private function upstream(string $id, array $labels): VocabularyClass
    {
        return new VocabularyClass($id, [], $labels);
    }

    /**
     * @param list<string> $languages
     */
    private function resolve(
        string $sourceValue,
        ?VocabularyClass $class,
        array $languages = ['de', 'en']
    ): TitleResolution {
        return (new TitleResolver())->resolve(
            $sourceValue,
            $class,
            $languages,
            self::FALLBACK,
            'de'
        );
    }

    #[Test]
    public function takesEachLanguageFromItsOwnUpstreamLabel(): void
    {
        $resolution = $this->resolve(
            'thuecat:BrassMusic',
            $this->upstream('thuecat:BrassMusic', ['de' => 'Blasmusik', 'en' => 'Brass music'])
        );

        $this->assertSame(['de' => 'Blasmusik', 'en' => 'Brass music'], $resolution->titles);
    }

    #[Test]
    public function upstreamResolutionDoesNotTouchTheFallbackMap(): void
    {
        $resolution = $this->resolve(
            'thuecat:BeerBar',
            $this->upstream('thuecat:BeerBar', ['de' => 'Bierbar upstream', 'en' => 'Beer bar'])
        );

        $this->assertFalse($resolution->usedFallback, 'The map was never needed.');
        $this->assertSame('Bierbar upstream', $resolution->titles['de'] ?? null);
    }

    #[Test]
    public function fallsBackToTheMapWhereUpstreamIsSilent(): void
    {
        $resolution = $this->resolve('thuecat:BeerBar', null);

        $this->assertSame('Bierbar', $resolution->titles['de'] ?? null);
        $this->assertTrue($resolution->usedFallback);
    }

    #[Test]
    public function aMissingTranslationIsNotTheFallbackMapsBusiness(): void
    {
        $resolution = $this->resolve(
            'thuecat:BeerBar',
            $this->upstream('thuecat:BeerBar', ['de' => 'Bierbar upstream']),
            ['de', 'en', 'fr']
        );

        // The map holds default-language titles only, so it could never have
        // supplied French. Counting that as "consulted" would report nearly
        // every type on a multilingual site.
        $this->assertFalse($resolution->usedFallback);
        $this->assertSame(['de' => 'Bierbar upstream'], $resolution->titles);
    }

    #[Test]
    public function anUntaggedLabelTitlesEnglish(): void
    {
        $resolution = $this->resolve(
            'schema:Bridge',
            $this->upstream('schema:Bridge', [VocabularyClass::UNTAGGED => 'Bridge'])
        );

        $this->assertSame('Bridge', $resolution->titles['en'] ?? null);
    }

    #[Test]
    public function anUntaggedLabelNeverTitlesTheDefaultLanguage(): void
    {
        $resolution = $this->resolve(
            'schema:Bridge',
            $this->upstream('schema:Bridge', [VocabularyClass::UNTAGGED => 'Bridge'])
        );

        // German comes from the map; showing "Bridge" under a German language
        // is exactly what this rule prevents.
        $this->assertSame('Brücke', $resolution->titles['de'] ?? null);
    }

    #[Test]
    public function aClassLabelledOnlyUntaggedDrawsOnBothSources(): void
    {
        $resolution = $this->resolve(
            'schema:Bridge',
            $this->upstream('schema:Bridge', [VocabularyClass::UNTAGGED => 'Bridge'])
        );

        $this->assertSame(['de' => 'Brücke', 'en' => 'Bridge'], $resolution->titles);
        $this->assertTrue($resolution->usedFallback, 'The map carried the default language.');
    }

    #[Test]
    public function aTaggedEnglishLabelWinsOverAnUntaggedOne(): void
    {
        $resolution = $this->resolve(
            'schema:Bridge',
            $this->upstream('schema:Bridge', [
                VocabularyClass::UNTAGGED => 'Bridge untagged',
                'en' => 'Bridge tagged',
            ])
        );

        $this->assertSame('Bridge tagged', $resolution->titles['en'] ?? null);
    }

    #[Test]
    public function anUntaggedLabelIsIgnoredWhenEnglishIsNotConfigured(): void
    {
        $resolution = $this->resolve(
            'schema:Bridge',
            $this->upstream('schema:Bridge', [VocabularyClass::UNTAGGED => 'Bridge']),
            ['de']
        );

        $this->assertSame(['de' => 'Brücke'], $resolution->titles);
    }

    #[Test]
    public function mixesUpstreamAndFallbackAcrossLanguages(): void
    {
        $resolution = $this->resolve(
            'thuecat:BeerBar',
            $this->upstream('thuecat:BeerBar', ['en' => 'Beer bar'])
        );

        $this->assertSame(['de' => 'Bierbar', 'en' => 'Beer bar'], $resolution->titles);
        $this->assertTrue($resolution->usedFallback);
    }

    #[Test]
    public function resolvesNothingWhenNeitherSourceNamesTheValue(): void
    {
        $resolution = $this->resolve('thuecat:Unknown', null);

        $this->assertSame([], $resolution->titles);
        $this->assertFalse($resolution->hasTitleFor('de'));
    }

    #[Test]
    public function reportsTheMapAsConsultedEvenWhenItHadNothingToGive(): void
    {
        $resolution = $this->resolve('thuecat:Unknown', null);

        // Consulting is the trigger, not succeeding: this is what the report
        // lists as unmatched.
        $this->assertTrue($resolution->usedFallback);
    }

    #[Test]
    public function neverNamesATermAfterItsOwnSourceValue(): void
    {
        $resolution = $this->resolve('thuecat:Unknown', null);

        $this->assertNotContains('thuecat:Unknown', $resolution->titles);
    }

    #[Test]
    public function ignoresAnUpstreamLabelForAnUnconfiguredLanguage(): void
    {
        $resolution = $this->resolve(
            'thuecat:BeerBar',
            $this->upstream('thuecat:BeerBar', [
                'de' => 'Bierbar upstream',
                'en' => 'Beer bar',
                'pl' => 'Piwiarnia',
            ])
        );

        $this->assertSame(['de' => 'Bierbar upstream', 'en' => 'Beer bar'], $resolution->titles);
    }
}
