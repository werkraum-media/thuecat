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

namespace WerkraumMedia\ThueCat\Import\UrlProvider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;

#[AutoconfigureTag('import.url.provider')]
interface UrlProvider
{
    /**
     * @return string[]
     */
    public function getUrls(?string $apiDomain = null): array;

    public function canProvideForConfiguration(ImportConfigurationInterface $configuration): bool;

    public function createWithConfiguration(ImportConfigurationInterface $configuration): UrlProvider;
}
