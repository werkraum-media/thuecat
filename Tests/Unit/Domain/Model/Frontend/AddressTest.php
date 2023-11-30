<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Address;

class AddressTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $subject = new Address('[]');

        self::assertInstanceOf(Address::class, $subject);
    }

    #[Test]
    public function returnsProperDefaults(): void
    {
        $subject = new Address('[]');

        self::assertSame('', $subject->getStreet());
        self::assertSame('', $subject->getZip());
        self::assertSame('', $subject->getCity());
        self::assertSame('', $subject->getEmail());
        self::assertSame('', $subject->getPhone());
        self::assertSame('', $subject->getFax());
    }

    #[Test]
    public function returnsStreet(): void
    {
        $subject = new Address('{"street": "Example Street 10"}');

        self::assertSame('Example Street 10', $subject->getStreet());
    }

    #[Test]
    public function returnsZip(): void
    {
        $subject = new Address('{"zip": "09084"}');

        self::assertSame('09084', $subject->getZip());
    }

    #[Test]
    public function returnsCity(): void
    {
        $subject = new Address('{"city": "Erfurt"}');

        self::assertSame('Erfurt', $subject->getCity());
    }

    #[Test]
    public function returnsEmail(): void
    {
        $subject = new Address('{"email": "example@example.com"}');

        self::assertSame('example@example.com', $subject->getEmail());
    }

    #[Test]
    public function returnsPhone(): void
    {
        $subject = new Address('{"phone": "+49 361 99999"}');

        self::assertSame('+49 361 99999', $subject->getPhone());
    }

    #[Test]
    public function returnsFax(): void
    {
        $subject = new Address('{"fax": "+49 361 99998"}');

        self::assertSame('+49 361 99998', $subject->getFax());
    }

    #[Test]
    public function returnsLatitude(): void
    {
        $subject = new Address('{"geo": {"latitude": 50.978765}}');

        self::assertSame(50.978765, $subject->getLatitute());
    }

    #[Test]
    public function returnsLongitude(): void
    {
        $subject = new Address('{"geo": {"longitude": 11.029133}}');

        self::assertSame(11.029133, $subject->getLongitude());
    }

    #[Test]
    public function returnsSerializedString(): void
    {
        $subject = new Address('{"street": "Example Street 10"}');

        self::assertSame('{"street": "Example Street 10"}', (string)$subject);
    }
}
