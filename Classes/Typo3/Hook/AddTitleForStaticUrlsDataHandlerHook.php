<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
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

namespace WerkraumMedia\ThueCat\Typo3\Hook;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Minimum;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\EntityRegistry;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;

/**
 * Will add a title for all url entries, based on the fetched name of the record within url.
 */
final class AddTitleForStaticUrlsDataHandlerHook
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly FetchData $fetchData,
        private readonly EntityRegistry $entityRegistry,
        private readonly EntityMapper $entityMapper,
    ) {
    }

    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        string|int $id,
    ): void {
        if ($this->shouldSkip($table, $incomingFieldArray)) {
            return;
        }

        $urls = ArrayUtility::getValueByPath($incomingFieldArray, 'configuration/data/sDEF/lDEF/urls/el');
        if (is_array($urls) === false || $urls === []) {
            return;
        }

        $this->addTitles(
            $incomingFieldArray,
            $urls,
            $this->determineLanguage($id, $incomingFieldArray)
        );
    }

    private function determineLanguage(string|int $id, array $incomingFieldArray): string
    {
        $pid = $incomingFieldArray['pid'] ?? '';
        if ($pid === '' && is_int($id)) {
            $pid = BackendUtility::getRecord('tx_thuecat_import_configuration', $id, 'pid')['pid'] ?? '';
        }

        if ($pid === '') {
            return '';
        }

        return $this->siteFinder
            ->getSiteByPageId((int)$pid)
            ->getDefaultLanguage()
            ->getLocale()
            ->getLanguageCode()
        ;
    }

    private function addTitles(array &$incomingFieldArray, array $urls, string $language): void
    {
        if ($language === '') {
            return;
        }

        foreach ($urls as $identifier => $values) {
            if (ArrayUtility::isValidPath($values, 'url/el/url/vDEF') === false) {
                continue;
            }

            $url = ArrayUtility::getValueByPath($values, 'url/el/url/vDEF');
            if (is_string($url) === false) {
                continue;
            }

            $mappedEntity = $this->mapUrlToEntity($url, $language);
            if (!$mappedEntity instanceof Minimum) {
                continue;
            }

            $incomingFieldArray['configuration']['data']['sDEF']['lDEF']['urls']['el'][$identifier]['url']['el']['title']['vDEF'] = $mappedEntity->getName();
        }
    }

    private function shouldSkip(string $table, array $incomingFieldArray): bool
    {
        return $table !== 'tx_thuecat_import_configuration'
            || ($incomingFieldArray['type'] ?? '') !== 'static'
            || ($incomingFieldArray['configuration'] ?? []) === []
            || ArrayUtility::isValidPath($incomingFieldArray, 'configuration/data/sDEF/lDEF/urls/el') === false;
    }

    private function mapUrlToEntity(string $url, string $language): null|object
    {
        if (GeneralUtility::isValidUrl($url) === false) {
            return null;
        }

        $jsonEntity = $this->fetchData->jsonLDFromUrl($url)['@graph'][0] ?? [];
        return $this->entityMapper->mapDataToEntity(
            $jsonEntity,
            $this->entityRegistry->getEntityByTypes($jsonEntity['@type']),
            [
                JsonDecode::ACTIVE_LANGUAGE => $language,
            ]
        );
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
    }
}
