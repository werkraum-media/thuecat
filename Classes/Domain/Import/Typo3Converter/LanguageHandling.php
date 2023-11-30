<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Domain\Import\Typo3Converter;

use InvalidArgumentException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Languages;

class LanguageHandling implements Languages
{
    /**
     * @var SiteFinder
     */
    private $siteFinder;

    public function __construct(
        SiteFinder $siteFinder
    ) {
        $this->siteFinder = $siteFinder;
    }

    public function getAvailable(ImportConfiguration $configuration): array
    {
        if (method_exists($configuration, 'getStoragePid') === false) {
            throw new InvalidArgumentException('Unsupported configuration, need to retrieve storage pid.', 1629710300);
        }
        return array_map(function (SiteLanguage $language) {
            return $language->getLocale()->getLanguageCode();
        }, $this->getLanguages($configuration->getStoragePid()));
    }

    public function getLanguageUidForString(int $pageUid, string $isoCode): int
    {
        foreach ($this->getLanguages($pageUid) as $language) {
            if ($language->getLocale()->getLanguageCode() === $isoCode) {
                return $language->getLanguageId();
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Could not find language for combination of page "%d" and iso code "%s".',
                $pageUid,
                $isoCode
            ),
            1628246493
        );
    }

    /**
     * @return SiteLanguage[]
     */
    private function getLanguages(int $pageUid): array
    {
        return $this->siteFinder->getSiteByPageId($pageUid)->getLanguages();
    }
}
