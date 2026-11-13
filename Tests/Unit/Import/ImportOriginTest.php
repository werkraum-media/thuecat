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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Import\ImportOrigin;

final class ImportOriginTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function syncIdByType(): iterable
    {
        yield 'syncScope' => ['syncScope', 'scope-id'];
        yield 'syncChannel' => ['syncChannel', 'channel-id'];
        yield 'static' => ['static', ''];
        yield 'containsPlace' => ['containsPlace', ''];
    }

    #[Test]
    #[DataProvider('syncIdByType')]
    public function takesTheSyncIdOfTheConfigurationType(string $type, string $syncId): void
    {
        $configuration = new class() extends ImportConfiguration {
            public function getSyncScopeId(): string
            {
                return 'scope-id';
            }

            public function getSyncChannelId(): string
            {
                return 'channel-id';
            }
        };
        $configuration->_setProperty('uid', 7);
        $configuration->_setProperty('title', 'Weimar events');
        $configuration->_setProperty('type', $type);

        $this->assertSame(
            [
                'origin_configuration_uid' => 7,
                'origin_configuration_title' => 'Weimar events',
                'origin_sync_id' => $syncId,
            ],
            ImportOrigin::fromConfiguration($configuration)->toFields()
        );
    }

    #[Test]
    public function anUnsavedConfigurationHasUidZero(): void
    {
        $this->assertSame(0, ImportOrigin::fromConfiguration(new ImportConfiguration())->configurationUid);
    }
}
