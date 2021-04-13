<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\UrlProvider;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

class StaticUrlProvider implements UrlProvider
{
    /**
     * @var string[]
     */
    private $urls = [];

    public function __construct(
        ImportConfiguration $configuration
    ) {
        if ($configuration instanceof ImportConfiguration) {
            $this->urls = $configuration->getUrls();
        }
    }

    public function canProvideForConfiguration(
        ImportConfiguration $configuration
    ): bool {
        return $configuration->getType() === 'static';
    }

    public function createWithConfiguration(
        ImportConfiguration $configuration
    ): UrlProvider {
        return GeneralUtility::makeInstance(self::class, $configuration);
    }

    public function getUrls(): array
    {
        return $this->urls;
    }
}
