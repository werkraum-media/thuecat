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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\VocabularyLabelResolver;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyProvider;

/**
 * Country and region arrive either as a language-tagged literal or as an
 * untagged CURIE; both must reach one stored value.
 */
class VocabularyLabelResolverTest extends TestCase
{
    private const GERMANY = 'https://thuecat.org/ontology/thuecat/1.0/Germany';

    private const THURINGIA = 'https://thuecat.org/ontology/thuecat/1.0/Thuringia';

    /** @var list<?string> */
    private array $apiKeys = [];

    #[Test]
    public function resolvesCurieToLabelForRequestedLanguage(): void
    {
        $subject = $this->subject([
            self::GERMANY => new VocabularyClass(self::GERMANY, [], ['de' => 'Deutschland', 'en' => 'Germany']),
        ]);

        self::assertSame('Deutschland', $subject->resolve('thuecat:Germany', 'de'));
        self::assertSame('Germany', $subject->resolve('thuecat:Germany', 'en'));
    }

    #[Test]
    public function resolvesRegionCurie(): void
    {
        $subject = $this->subject([
            self::THURINGIA => new VocabularyClass(self::THURINGIA, [], ['de' => 'Thüringen']),
        ]);

        self::assertSame('Thüringen', $subject->resolve('thuecat:Thuringia', 'de'));
    }

    #[Test]
    public function keepsLiteralUntouched(): void
    {
        $subject = $this->subject([]);

        self::assertSame('Deutschland', $subject->resolve('Deutschland', 'de'));
        self::assertSame('Thüringen', $subject->resolve('Thüringen', 'de'));
    }

    #[Test]
    public function keepsEmptyValueEmpty(): void
    {
        self::assertSame('', $this->subject([])->resolve('', 'de'));
    }

    /**
     * The three ways the vocabulary can fail to answer all degrade to the bare
     * member name, which is what the import stores for every other enum.
     */
    #[Test]
    public function fallsBackToMemberNameWhenClassIsUnknown(): void
    {
        $subject = $this->subject([]);

        self::assertSame('Germany', $subject->resolve('thuecat:Germany', 'de'));
    }

    #[Test]
    public function fallsBackToMemberNameWhenLanguageHasNoLabel(): void
    {
        $subject = $this->subject([
            self::GERMANY => new VocabularyClass(self::GERMANY, [], ['de' => 'Deutschland']),
        ]);

        self::assertSame('Germany', $subject->resolve('thuecat:Germany', 'fr'));
    }

    #[Test]
    public function fallsBackToMemberNameWhenVocabularyFails(): void
    {
        $provider = self::createStub(VocabularyProvider::class);
        $provider->method('index')->willThrowException(new RuntimeException('upstream down', 1758000000));

        $subject = new VocabularyLabelResolver($provider);

        self::assertSame('Germany', $subject->resolve('thuecat:Germany', 'de'));
    }

    /**
     * The run's key reaches the vocabulary, as it does for every other index()
     * call in the import; without it the fetch behaves differently here.
     */
    #[Test]
    public function passesApiKeyOfTheRunToTheVocabulary(): void
    {
        $subject = $this->subject([
            self::GERMANY => new VocabularyClass(self::GERMANY, [], ['de' => 'Deutschland']),
        ]);

        $subject->resolve('thuecat:Germany', 'de', 'the-api-key');

        self::assertSame(['the-api-key'], $this->apiKeys);
    }

    /**
     * @param array<string, VocabularyClass> $classes
     */
    private function subject(array $classes): VocabularyLabelResolver
    {
        $index = new VocabularyIndex($classes);

        $provider = self::createStub(VocabularyProvider::class);
        $provider->method('index')->willReturnCallback(
            function (?string $apiKey = null) use ($index): VocabularyIndex {
                $this->apiKeys[] = $apiKey;
                return $index;
            }
        );

        return new VocabularyLabelResolver($provider);
    }
}
