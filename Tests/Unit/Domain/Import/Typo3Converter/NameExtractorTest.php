<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Typo3Converter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Person;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Place;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\NameExtractor;

class NameExtractorTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $resolveForeignReference = self::createStub(ResolveForeignReference::class);

        $subject = new NameExtractor(
            $resolveForeignReference
        );

        self::assertInstanceOf(
            NameExtractor::class,
            $subject
        );
    }

    #[Test]
    public function extractsNameFromString(): void
    {
        $resolveForeignReference = self::createStub(ResolveForeignReference::class);

        $subject = new NameExtractor(
            $resolveForeignReference
        );

        self::assertSame(
            'Full Name',
            $subject->extract('Full Name', 'de')
        );
    }

    #[Test]
    public function extractsNameFromForeignReference(): void
    {
        $place = self::createStub(Place::class);
        $place->method('getName')->willReturn('Full Name');
        $resolveForeignReference = $this->createResolverForObject($place);

        $foreignReference = self::createStub(ForeignReference::class);

        $subject = new NameExtractor(
            $resolveForeignReference
        );

        self::assertSame(
            'Full Name',
            $subject->extract($foreignReference, 'de')
        );
    }

    #[Test]
    public function extractsCombinedNameFromForeignReference(): void
    {
        $person = self::createStub(Person::class);
        $person->method('getGivenName')->willReturn('Full');
        $person->method('getFamilyName')->willReturn('Name');
        $resolveForeignReference = $this->createResolverForObject($person);

        $foreignReference = self::createStub(ForeignReference::class);

        $subject = new NameExtractor(
            $resolveForeignReference
        );

        self::assertSame(
            'Full Name',
            $subject->extract($foreignReference, 'de')
        );
    }

    #[Test]
    public function extractsCombinedNameFromForeignReferenceInsteadOfName(): void
    {
        $person = self::createStub(Person::class);
        $person->method('getName')->willReturn('Low Priority');
        $person->method('getGivenName')->willReturn('Full');
        $person->method('getFamilyName')->willReturn('Name');
        $resolveForeignReference = $this->createResolverForObject($person);

        $foreignReference = self::createStub(ForeignReference::class);

        $subject = new NameExtractor(
            $resolveForeignReference
        );

        self::assertSame(
            'Full Name',
            $subject->extract($foreignReference, 'de')
        );
    }

    /**
     * @return Stub&ResolveForeignReference
     */
    private function createResolverForObject(object $object): Stub
    {
        $resolveForeignReference = self::createStub(ResolveForeignReference::class);
        $resolveForeignReference->method('resolve')->willReturn($object);

        return $resolveForeignReference;
    }
}
