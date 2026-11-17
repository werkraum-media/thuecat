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
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailLocationEntity;

final class TrailLocationEntityTest extends TestCase
{
    #[Test]
    public function writesCoordinatesAsPlainDecimalsOfAtMostSevenDecimals(): void
    {
        $geo = [
            'schema:latitude' => ['@type' => 'schema:Float', '@value' => '5.0974887E1'],
            'schema:longitude' => ['@type' => 'schema:Float', '@value' => '11.32717412345'],
        ];

        $entity = new TrailLocationEntity();
        $entity->configure(['schema:geo' => $geo], 'de', 'https://thuecat.org/resources/test-trail-1');

        $result = $entity->toArray();

        $this->assertSame('50.974887', $result['latitude'] ?? null);
        $this->assertSame('11.3271741', $result['longitude'] ?? null);
    }
}
