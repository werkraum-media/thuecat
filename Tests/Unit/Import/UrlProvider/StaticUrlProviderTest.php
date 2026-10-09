<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\UrlProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Import\UrlProvider\StaticUrlProvider;

class StaticUrlProviderTest extends TestCase
{
    #[Test]
    public function canProvideForStaticConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'static');

        $subject = new StaticUrlProvider();

        $result = $subject->canProvideForConfiguration($configuration);
        self::assertTrue($result);
    }

    #[Test]
    public function returnsConcreteProviderForConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', ['https://example.com']);

        $subject = new StaticUrlProvider();

        $result = $subject->createWithConfiguration($configuration);
        self::assertInstanceOf(StaticUrlProvider::class, $result);
    }

    #[Test]
    public function concreteProviderReturnsUrls(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', ['https://example.com']);

        $subject = new StaticUrlProvider();

        $concreteProvider = $subject->createWithConfiguration($configuration);
        $result = $concreteProvider->getUrls();
        self::assertSame([
            'https://example.com',
        ], $result);
    }
}
