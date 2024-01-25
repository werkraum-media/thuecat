<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;

class TouristAttractionTest extends TestCase
{
    #[Test]
    public function returnsSingleSlogan(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('slogan', 'Some text');

        self::assertSame('Some text', $subject->getSlogan());
        self::assertSame(['Some text'], $subject->getSlogans());
    }

    #[Test]
    public function returnsMultipleSlogans(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('slogan', 'Some text,Highlight');

        self::assertSame('Some text', $subject->getSlogan());
        self::assertSame(['Some text', 'Highlight'], $subject->getSlogans());
    }
}
