<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Frontend\Cache;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;
use WerkraumMedia\ThueCat\Extension;

/**
 * Renders one list item and keeps the result.
 *
 * Deliberately not a ViewHelper: an integrator overriding the list template
 * would silently lose caching, with nothing failing to say so.
 *
 * Table and template are derived from the record, so a further record type
 * gaining list output needs no change here.
 */
class TeaserRenderer
{
    public function __construct(
        protected readonly CacheManager $cacheManager,
        protected readonly ViewFactoryInterface $viewFactory,
        protected readonly CacheIdentifierFactory $identifierFactory,
        protected readonly DataMapper $dataMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     * @param array{templateRootPaths?: array<int, string>, partialRootPaths?: array<int, string>, layoutRootPaths?: array<int, string>} $viewPaths
     */
    public function render(
        Base $record,
        int $detailPageUid,
        int $languageId,
        array $settings,
        array $viewPaths,
        ServerRequestInterface $request
    ): string {
        $cache = $this->cacheManager->getCache(Extension::CACHE_TEASER);
        $table = $this->tableFor($record);
        $uid = $record->getUid() ?? 0;

        $identifier = $this->identifierFactory->forTeaser($table, $uid, $detailPageUid, $languageId);

        $cached = $cache->get($identifier);
        if (is_string($cached)) {
            return $cached;
        }

        $html = $this->renderTemplate($record, $settings, $viewPaths, $request);

        // Per-uid only: DataHandler queues the bare <table> tag on every save,
        // so tagging with it would discard every teaser of that type.
        $cache->set($identifier, $html, [$table . '_' . $uid]);

        return $html;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array{templateRootPaths?: array<int, string>, partialRootPaths?: array<int, string>, layoutRootPaths?: array<int, string>} $viewPaths
     */
    protected function renderTemplate(
        Base $record,
        array $settings,
        array $viewPaths,
        ServerRequestInterface $request
    ): string {
        // Paths from the plugin's configuration, so overrides keep working.
        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: $viewPaths['templateRootPaths'] ?? null,
            partialRootPaths: $viewPaths['partialRootPaths'] ?? null,
            layoutRootPaths: $viewPaths['layoutRootPaths'] ?? null,
            request: $request,
        ));
        $view->assignMultiple([
            // Each type's template names its own subject.
            $this->variableNameFor($record) => $record,
            'record' => $record,
            'settings' => $settings,
        ]);

        return $view->render($this->typeFor($record) . '/ListItem');
    }

    /**
     * Declared name, else the lower-cased model name — the fallback keeps a new
     * type free of code here.
     */
    protected function variableNameFor(Base $record): string
    {
        $declared = $record::TEMPLATE_VARIABLE_NAME;

        return is_string($declared) && $declared !== ''
            ? $declared
            : lcfirst($this->typeFor($record));
    }

    /** Unqualified model name: `TouristAttraction` → `TouristAttraction/ListItem`. */
    protected function typeFor(Base $record): string
    {
        $parts = explode('\\', $record::class);

        return (string)end($parts);
    }

    protected function tableFor(Base $record): string
    {
        return $this->dataMapper->getDataMap($record::class)->getTableName();
    }
}
