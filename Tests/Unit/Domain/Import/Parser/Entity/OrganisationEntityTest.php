<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Fake\ParserContextFake;

final class OrganisationEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsTableName(): void
    {
        $subject = new OrganisationEntity();

        self::assertSame('tx_thuecat_organisation', $subject->table);
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
        $subject->configure($node, new ParserContextFake());

        $row = $subject->toArray();

        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
    }

    #[Test]
    public function returnsDescription(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->configure($node, new ParserContextFake());

        $row = $subject->toArray();

        self::assertStringStartsWith('Die Erfurt Tourismus', $row['description']);
    }

    #[Test]
    public function rowContainsRemoteId(): void
    {
        $node = $this->nodeFromFixture('018132452787-ngbe.json', 'schema:Organization');
        self::assertNotNull($node);
        $subject = new OrganisationEntity();
        $subject->configure($node, new ParserContextFake());

        $row = $subject->toArray();

        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
    }
}
