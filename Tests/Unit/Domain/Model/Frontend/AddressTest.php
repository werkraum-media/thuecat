<?php

declare(strict_types=1);

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
    public function returnsProperDefaults(): void
    {
        $subject = new Address();

        self::assertSame('', $subject->getStreet());
        self::assertSame('', $subject->getZip());
        self::assertSame('', $subject->getCity());
        self::assertSame('', $subject->getEmail());
        self::assertSame('', $subject->getPhone());
        self::assertSame('', $subject->getFax());
        self::assertSame(0.0, $subject->getLatitude());
        self::assertSame(0.0, $subject->getLongitude());
    }

    #[Test]
    public function returnsStreet(): void
    {
        self::assertSame('Example Street 10', $this->subjectWith('street', 'Example Street 10')->getStreet());
    }

    #[Test]
    public function returnsZip(): void
    {
        self::assertSame('09084', $this->subjectWith('zip', '09084')->getZip());
    }

    #[Test]
    public function returnsCity(): void
    {
        self::assertSame('Example City', $this->subjectWith('city', 'Example City')->getCity());
    }

    #[Test]
    public function returnsEmail(): void
    {
        self::assertSame('mail@example.com', $this->subjectWith('email', 'mail@example.com')->getEmail());
    }

    #[Test]
    public function returnsPhone(): void
    {
        self::assertSame('+49 361 1234', $this->subjectWith('phone', '+49 361 1234')->getPhone());
    }

    #[Test]
    public function returnsFax(): void
    {
        self::assertSame('+49 361 5678', $this->subjectWith('fax', '+49 361 5678')->getFax());
    }

    /**
     * Full precision survives the read: the varchar column holds what the
     * import wrote, and only a decimal column would round it.
     */
    #[Test]
    public function returnsLatitude(): void
    {
        $subject = new Address();
        $subject->_setProperty('latitude', 50.978765);

        self::assertSame(50.978765, $subject->getLatitude());
    }

    #[Test]
    public function returnsLongitude(): void
    {
        $subject = new Address();
        $subject->_setProperty('longitude', 11.029133);

        self::assertSame(11.029133, $subject->getLongitude());
    }

    /** @param non-empty-string $property */
    private function subjectWith(string $property, string $value): Address
    {
        $subject = new Address();
        $subject->_setProperty($property, $value);

        return $subject;
    }
}
