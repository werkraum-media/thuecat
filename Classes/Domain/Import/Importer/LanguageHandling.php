<?php

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class LanguageHandling
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
