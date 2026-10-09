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
use WerkraumMedia\ThueCat\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

final class OrganisationEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsTableName(): void
    {
        $subject = new OrganisationEntity();

        self::assertSame('tx_thuecat_organisation', $subject::TABLE);
    }

    #[Test]
    public function returnsRemoteId(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();

        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $subject->getRemoteId($node));
    }

    #[Test]
    public function returnsTitle(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
    }

    #[Test]
    public function returnsDescription(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertStringStartsWith('Die Erfurt Tourismus', (string)$row['description']);
    }

    #[Test]
    public function titleAndDescriptionAreOmittedForUnmatchedLanguage(): void
    {
        // Fixture only carries German entries; picking a language that is not
        // present must yield '' rather than silently falling back to German.
        // toArray() then drops the empty strings, so the keys disappear entirely.
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->parse($node, 'en', new ParserContext(0));

        $row = $subject->toArray();

        self::assertArrayNotHasKey('title', $row);
        self::assertArrayNotHasKey('description', $row);
    }

    #[Test]
    public function rowContainsRemoteId(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->parse($node, 'de', new ParserContext(0));

        $row = $subject->toArray();

        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
    }
}
