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

namespace WerkraumMedia\ThueCat\Import\UrlProvider;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;

readonly class UrlProviders
{
    /**
     * @param ServiceLocator<mixed> $urlProviders
     */
    public function __construct(
        #[AutowireLocator(services: 'import.url.provider')]
        protected ServiceLocator $urlProviders,
    ) {
    }

    public function forConfiguration(ImportConfigurationInterface $configuration): UrlProvider
    {
        foreach ($this->urlProviders as $provider) {
            if ($provider instanceof UrlProvider && $provider->canProvideForConfiguration($configuration)) {
                return $provider->createWithConfiguration($configuration);
            }
        }

        throw new InvalidUrlProviderException('No URL Provider available for given configuration.', 1629296635);
    }
}
