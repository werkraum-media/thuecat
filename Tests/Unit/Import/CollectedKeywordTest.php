<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

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
use WerkraumMedia\ThueCat\Import\CollectedKeyword;

/**
 * A keyword's titles have to survive from parsing to persistence: the term
 * entity resolves one per language, and a single flat title would discard every
 * one but the default.
 */
class CollectedKeywordTest extends TestCase
{
    /**
     * @param array<string, string> $titles
     */
    private function keyword(array $titles): CollectedKeyword
    {
        return new CollectedKeyword(
            'tx_thuecat_tourist_attraction',
            '1',
            'keywords',
            'keyword:https://thuecat.org/resources/term',
            $titles
        );
    }

    #[Test]
    public function carriesATitlePerLanguage(): void
    {
        $keyword = $this->keyword(['de' => 'Blasmusik', 'en' => 'Brass music']);

        self::assertSame('Blasmusik', $keyword->titleFor('de'));
        self::assertSame('Brass music', $keyword->titleFor('en'));
    }

    #[Test]
    public function reportsNoTitleForAnUntranslatedLanguage(): void
    {
        $keyword = $this->keyword(['de' => 'Blasmusik']);

        self::assertNull($keyword->titleFor('en'));
    }

    #[Test]
    public function treatsAnEmptyTitleAsAbsent(): void
    {
        $keyword = $this->keyword(['de' => 'Blasmusik', 'en' => '']);

        self::assertNull($keyword->titleFor('en'), 'An empty label is no label.');
    }

    #[Test]
    public function exposesEveryTitleItCarries(): void
    {
        $keyword = $this->keyword(['de' => 'Blasmusik', 'en' => 'Brass music']);

        self::assertSame(['de' => 'Blasmusik', 'en' => 'Brass music'], $keyword->titles);
    }
}
