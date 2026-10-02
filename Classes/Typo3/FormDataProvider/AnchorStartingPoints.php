<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Typo3\FormDataProvider;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;

/**
 * Resolves `###THUECAT_ANCHOR:<table>:<kind>###` in a category tree's
 * starting points through the same chain the import writes with, so a form
 * offers the tree its records were filed in.
 */
#[Autoconfigure(public: true)]
class AnchorStartingPoints implements FormDataProviderInterface
{
    protected const MARKER = '/###THUECAT_ANCHOR:([a-z0-9_]+):(category|keywords)###/';

    public function __construct(
        protected readonly CategoryAnchorResolver $anchorResolver,
    ) {
    }

    public function addData(array $result): array
    {
        $processedTca = $result['processedTca'] ?? null;
        if (!is_array($processedTca) || !is_array($processedTca['columns'] ?? null)) {
            return $result;
        }

        $site = $result['site'] ?? null;
        $columns = $processedTca['columns'];
        foreach ($columns as $field => $column) {
            if (!is_array($column) || !is_array($column['config'] ?? null) || !is_array($column['config']['treeConfig'] ?? null)) {
                continue;
            }
            $startingPoints = $column['config']['treeConfig']['startingPoints'] ?? null;
            if (!is_string($startingPoints) || !str_contains($startingPoints, '###THUECAT_ANCHOR:')) {
                continue;
            }

            $column['config']['treeConfig']['startingPoints'] = $this->resolveMarkers(
                $startingPoints,
                $site instanceof Site ? $site : null
            );
            $columns[$field] = $column;
        }
        $processedTca['columns'] = $columns;
        $result['processedTca'] = $processedTca;

        return $result;
    }

    /**
     * 0 is what core makes of an unset ###SITE### value: the whole tree.
     */
    protected function resolveMarkers(string $startingPoints, ?Site $site): string
    {
        return (string)preg_replace_callback(
            self::MARKER,
            fn (array $match): string => $site === null
                ? '0'
                : (string)$this->anchorResolver->resolvePair(
                    $site,
                    $match[1],
                    $match[2] === 'category' ? AnchorKind::Category : AnchorKind::Keyword
                )->parent,
            $startingPoints
        );
    }
}
