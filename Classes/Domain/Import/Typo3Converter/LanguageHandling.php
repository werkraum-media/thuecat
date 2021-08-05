<?php

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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Languages;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

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
        return array_map(function (SiteLanguage $language) {
            return $language->getTwoLetterIsoCode();
        }, $this->getLanguages($configuration->getStoragePid()));
    }

    public function getLanguageUidForString(int $pageUid, string $isoCode): int
    {
        $languages = $this->siteFinder->getSiteByPageId($pageUid)->getLanguages();
        foreach ($languages as $language) {
            if ($language->getTwoLetterIsoCode() === $isoCode) {
                return $language->getLanguageId();
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Could not find language for combination of page "%d" and iso code "%s".',
                $pageUid,
                $isoCode
            ),
            1628246493
        );
    }

    // TODO: Check usages and remove below methods

    /**
     * @return SiteLanguage[]
     */
    public function getLanguages(int $pageUid): array
    {
        return $this->siteFinder->getSiteByPageId($pageUid)->getLanguages();
    }

    public function getDefaultLanguage(int $pageUid): SiteLanguage
    {
        return $this->siteFinder->getSiteByPageId($pageUid)->getDefaultLanguage();
    }
}
