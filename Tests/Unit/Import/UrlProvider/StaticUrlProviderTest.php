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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationUrl;
use WerkraumMedia\ThueCat\Import\UrlProvider\StaticUrlProvider;

final class StaticUrlProviderTest extends TestCase
{
    #[Test]
    public function canProvideForStaticConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'static');

        $subject = new StaticUrlProvider();

        $result = $subject->canProvideForConfiguration($configuration);
        $this->assertTrue($result);
    }

    #[Test]
    public function returnsConcreteProviderForConfiguration(): void
    {
        $url = new ImportConfigurationUrl();
        $url->_setProperty('url', 'https://example.com');

        $urls = new ObjectStorage();
        $urls->attach($url);

        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', $urls);

        $subject = new StaticUrlProvider();

        $result = $subject->createWithConfiguration($configuration);
        $this->assertInstanceOf(StaticUrlProvider::class, $result);
    }

    #[Test]
    public function concreteProviderReturnsUrls(): void
    {
        $url = new ImportConfigurationUrl();
        $url->_setProperty('url', 'https://example.com');

        $urls = new ObjectStorage();
        $urls->attach($url);

        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', $urls);

        $subject = new StaticUrlProvider();

        $concreteProvider = $subject->createWithConfiguration($configuration);
        $result = $concreteProvider->getUrls();
        $this->assertSame([
            'https://example.com',
        ], $result);
    }
}
