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

namespace WerkraumMedia\ThueCat\Tests\Functional\DetailRecordKind;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKinds;

final class DetailRecordKindsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extbase',
        'filelist',
        'filemetadata',
        'install',
    ];

    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat',
        'werkraummedia/events',
        __DIR__ . '/../Fixtures/Extensions/DetailKind',
    ];

    #[Test]
    public function kindFromAnotherExtensionIsRegistered(): void
    {
        $subject = $this->get(DetailRecordKinds::class);

        self::assertSame(
            ['attraction', 'trail', 'example'],
            array_map(static fn ($kind): string => $kind->identifier, $subject->all())
        );
        self::assertSame('tx_thuecattestdetailkind_example', $subject->get('example')->table);
    }
}
