<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
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

namespace WerkraumMedia\ThueCat\Typo3\Hook;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * Will add a title for all url entries, based on the fetched name of the record within url.
 */
final readonly class AddTitleForStaticUrlsDataHandlerHook
{
    private const string TABLE = 'tx_thuecat_import_configuration_url';

    public function __construct(
        private SiteFinder $siteFinder,
        private FetchData $fetchData,
        // this finds and instantiates all Classes implementing the EntityInterface (which contains the service tag)
        #[AutowireLocator(services: 'import.entity')]
        private ServiceLocator $entities,
    ) {
    }

    /**
     * @param array<string, mixed> $incomingFieldArray
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        string|int $id,
    ): void {
        if ($table !== self::TABLE) {
            return;
        }

        $url = $incomingFieldArray['url'] ?? null;
        if (!is_string($url) || $url === '') {
            return;
        }

        $language = $this->determineLanguage($id, $incomingFieldArray);
        if ($language === '') {
            return;
        }

        $graphData = $this->fetchData->jsonLDFromUrl($url);
        $graph = is_array($graphData['@graph'] ?? null) ? $graphData['@graph'] : [];
        $node = is_array($graph[0] ?? null) ? $graph[0] : [];

        $entity = $this->resolveEntityClass($node['@type'] ?? []);
        if (!$entity instanceof EntityInterface) {
            return;
        }

        $entity->parse($node, $language, new ParserContext(0), []);
        $incomingFieldArray['title'] = $entity->toArray()['title'];
    }

    /**
     * @param array<string, mixed> $incomingFieldArray
     */
    private function determineLanguage(string|int $id, array $incomingFieldArray): string
    {
        $pid = $incomingFieldArray['pid'] ?? '';
        if ($pid === '' && is_int($id)) {
            $record = BackendUtility::getRecord(self::TABLE, $id, 'pid');
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
        // (TouristInformation, say) win over generic ones.
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
            static fn (EntityInterface $a, EntityInterface $b): int => $b->getPriority() <=> $a->getPriority()
        );

        return $candidates[0];
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
    }
}
