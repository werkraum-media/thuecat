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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TownEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class TownEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsTableName(): void
    {
        $subject = new TownEntity();

        self::assertSame('tx_thuecat_town', $subject::TABLE);
    }

    #[Test]
    public function returnsRemoteId(): void
    {
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        self::assertSame('https://thuecat.org/resources/043064193523-jcyt', $subject->getRemoteId($node));
    }

    #[Test]
    public function returnsTitle(): void
    {
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertSame('Erfurt', $row['title']);
    }

    #[Test]
    public function returnsDescription(): void
    {
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertStringStartsWith('Krämerbrücke, Dom, Alte Synagoge – die', (string)$row['description']);
    }

    #[Test]
    public function rowOmitsResolverOwnedColumns(): void
    {
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertArrayNotHasKey('managed_by', $row);
    }

    #[Test]
    public function titleAndDescriptionAreOmittedForUnmatchedLanguage(): void
    {
        // Fixture only carries German entries; picking a language that is not
        // present must yield '' rather than silently falling back to German.
        // toArray() then drops the empty strings, so the keys disappear entirely.
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        $subject->parse($node, 'en', new ParserContext(0));

        $row = $subject->toArray();

        self::assertArrayNotHasKey('title', $row);
        self::assertArrayNotHasKey('description', $row);
    }

    #[Test]
    public function capturesManagedByFromTopLevelThuecatField(): void
    {
        // Town uses thuecat:managedBy directly, where attractions
        // encode the same relation as thuecat:contentResponsible. The bucket
        // key is 'managedBy' in both cases so the resolver treats them the same.
        $node = $this->nodeFromFixture('043064193523-jcyt.json', 'schema:City');
        self::assertNotNull($node);
        $subject = new TownEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $transients = $subject->getTransients();

        self::assertArrayHasKey('managedBy', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/018132452787-ngbe'],
            $transients['managedBy']
        );
    }
}
