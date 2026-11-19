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

namespace WerkraumMedia\ThueCat\Tests\Unit\Removal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;

final class SelectionTest extends TestCase
{
    /**
     * @return iterable<string, array{Selection, array<string, string|int>}>
     */
    public static function selections(): iterable
    {
        yield 'a kind within a folder' => [
            Selection::ofKind('tx_thuecat_trail', Scope::Folder, 10),
            ['table' => 'tx_thuecat_trail', 'scope' => 'folder', 'page' => 10],
        ];
        yield 'an import within a site' => [
            Selection::ofOrigin(1, 32),
            ['origin' => 32, 'page' => 1],
        ];
    }

    /**
     * @param array<string, string|int> $parameters
     */
    #[DataProvider('selections')]
    #[Test]
    public function travelsAsParametersAndBack(Selection $selection, array $parameters): void
    {
        $this->assertSame($parameters, $selection->toParameters());
        $this->assertEquals($selection, Selection::fromParameters($parameters));
    }
}
