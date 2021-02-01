<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Converter;

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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Registry;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\Registry
 */
class RegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new Registry();

        self::assertInstanceOf(Registry::class, $subject);
    }

    /**
     * @test
     */
    public function allowsRegistrationOfConverter(): void
    {
        $subject = new Registry();
        $converter = $this->prophesize(Converter::class);

        $subject->registerConverter($converter->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function returnsConverterForMatchingType(): void
    {
        $subject = new Registry();
        $converter = $this->prophesize(Converter::class);
        $converter->canConvert(['thuecat:Entity'])->willReturn(true);
        $subject->registerConverter($converter->reveal());

        $result = $subject->getConverterBasedOnType(['thuecat:Entity']);
        self::assertSame($converter->reveal(), $result);
    }

    /**
     * @test
     */
    public function returnsFirstMatchingConverterForMatchingType(): void
    {
        $subject = new Registry();

        $converter1 = $this->prophesize(Converter::class);
        $converter1->canConvert(['thuecat:Entity'])->willReturn(true);
        $converter2 = $this->prophesize(Converter::class);
        $converter2->canConvert(['thuecat:Entity'])->willReturn(true);

        $subject->registerConverter($converter1->reveal());
        $subject->registerConverter($converter2->reveal());
        $result = $subject->getConverterBasedOnType(['thuecat:Entity']);
        self::assertSame($converter1->reveal(), $result);
    }

    /**
     * @test
     */
    public function returnsNullForNoMatchingConverter(): void
    {
        $subject = new Registry();

        $converter1 = $this->prophesize(Converter::class);
        $converter1->canConvert(['thuecat:Entity'])->willReturn(false);

        $subject->registerConverter($converter1->reveal());

        $result = $subject->getConverterBasedOnType(['thuecat:Entity']);

        self::assertSame(null, $result);
    }
}
