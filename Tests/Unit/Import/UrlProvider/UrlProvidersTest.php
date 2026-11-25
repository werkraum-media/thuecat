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
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Import\UrlProvider\InvalidUrlProviderException;
use WerkraumMedia\ThueCat\Import\UrlProvider\StaticUrlProvider;
use WerkraumMedia\ThueCat\Import\UrlProvider\UrlProviders;

final class UrlProvidersTest extends TestCase
{
    #[Test]
    public function refusesAConfigurationNoProviderServes(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'syncChannel');

        $subject = new UrlProviders(new ServiceLocator(['static' => static fn (): StaticUrlProvider => new StaticUrlProvider()]));

        $this->expectException(InvalidUrlProviderException::class);
        $this->expectExceptionCode(1629296635);

        $subject->forConfiguration($configuration);
    }
}
