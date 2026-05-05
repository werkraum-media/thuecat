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

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\EntityInterface;

/**
 * Will add a title for all url entries, based on the fetched name of the record within url.
 */
final class AddTitleForStaticUrlsDataHandlerHook
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly FetchData $fetchData,
        // this finds and instantiates all Classes implementing the EntityInterface (which contains the service tag)
        #[AutowireLocator(services: 'import.entity')]
        private readonly ServiceLocator $entities,
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
            $record = BackendUtility::getRecord('tx_thuecat_import_configuration', $id, 'pid');
            $pid = is_array($record) ? ($record['pid'] ?? '') : '';
        }

        if (!is_int($pid) && !is_string($pid)) {
            return '';
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
            if (!is_array($values)) {
                continue;
            }

            if (ArrayUtility::isValidPath($values, 'url/el/url/vDEF') === false) {
                continue;
            }

            $url = ArrayUtility::getValueByPath($values, 'url/el/url/vDEF');
            if (is_string($url) === false) {
                continue;
            }

            $graphData = $this->fetchData->jsonLDFromUrl($url);
            $graph = is_array($graphData['@graph'] ?? null) ? $graphData['@graph'] : [];
            $node = is_array($graph[0] ?? null) ? $graph[0] : [];

            $entity = $this->resolveEntityClass($node['@type'] ?? []);
            if ($entity === null) {
                continue;
            }
            $entity->parse($node, $language, []);
            $title = $entity->toArray()['title'];

            /** @var array<mixed> $configuration */
            $configuration = is_array($incomingFieldArray['configuration']) ? $incomingFieldArray['configuration'] : [];
            $configuration = ArrayUtility::setValueByPath(
                $configuration,
                'data/sDEF/lDEF/urls/el/' . $identifier . '/url/el/title/vDEF',
                $title
            );
            $incomingFieldArray['configuration'] = $configuration;
        }
    }

    private function shouldSkip(string $table, array $incomingFieldArray): bool
    {
        return $table !== 'tx_thuecat_import_configuration'
            || ($incomingFieldArray['type'] ?? '') !== 'static'
            || ($incomingFieldArray['configuration'] ?? []) === []
            || ArrayUtility::isValidPath($incomingFieldArray, 'configuration/data/sDEF/lDEF/urls/el') === false;
    }

    /**
     * Based on @type, the correct Entity class for the node is determined and returned.
     */
    private function resolveEntityClass(mixed $types): ?EntityInterface
    {
        $types = is_array($types) ? $types : [];
        if ($types === []) {
            return null;
        }

        // A JSON-LD node usually carries multiple @types (e.g. a TouristAttraction
        // is also Place, Thing, CivicStructure…). Collect every entity that claims
        // any of them, then let priority break ties — more specific entities
        // (TouristInformation, priority 20) win over generic ones.
        /** @var EntityInterface[] $candidates */
        $candidates = [];
        foreach ($this->entities as $candidate) {
            if (!$candidate instanceof EntityInterface) {
                continue;
            }
            foreach ($types as $type) {
                if (in_array($type, $candidate->handlesTypes(), true)) {
                    $candidates[] = $candidate;
                    break;
                }
            }
        }
        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (EntityInterface $a, EntityInterface $b) => $b->getPriority() <=> $a->getPriority()
        );

        return $candidates[0];
    }

    public static function register(): void
    {
        /** @var array{SC_OPTIONS: array{'t3lib/class.t3lib_tcemain.php': array{processDatamapClass: list<class-string>}}} $typo3ConfVars */
        $typo3ConfVars = &$GLOBALS['TYPO3_CONF_VARS'];
        $typo3ConfVars['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
    }
}
