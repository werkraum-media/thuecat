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

namespace WerkraumMedia\ThueCat\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\EditorFilter;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Import\SitePageIds;
use WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition;
use WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\FilterOptionProvider;

/**
 * What every filter field of the search mask offers.
 *
 * Resolves one scope per request and builds every tagged field against it, so
 * the answer to "which values does this filter offer" is decided once rather
 * than per field. Holds nothing a request resolved: scope, anchors and options
 * live in the values handed back.
 *
 * The record kind is named by the caller. A search plugin knows what it
 * searches; a sibling list only narrows how far that search reaches.
 */
#[Autoconfigure(public: true)]
class SearchFilterOptionsService
{
    public function __construct(
        #[AutowireLocator(services: 'search.filter.field')]
        private readonly ServiceLocator $fields,
        #[AutowireLocator(services: 'search.filter.option.provider')]
        private readonly ServiceLocator $providers,
        private readonly FrontendCategoryAnchors $anchors,
        private readonly SitePageIds $sitePageIds,
    ) {
    }

    /**
     * @param int[] $storagePageIds Empty when no sibling list bounds the mask.
     *
     * @return array<string, FilterOptions>
     */
    public function build(
        ServerRequestInterface $request,
        string $recordTable,
        array $storagePageIds,
        ?EditorFilter $editorFilter = null,
    ): array {
        $scope = $this->resolveScope($request, $recordTable, $storagePageIds, $editorFilter);

        $options = [];
        foreach (array_keys($this->fields->getProvidedServices()) as $id) {
            $field = $this->fields->get($id);
            if (!$field instanceof FilterFieldDefinition) {
                continue;
            }

            $options[$field->getName()] = $this->buildField($field, $scope);
        }

        return $options;
    }

    /**
     * One scope for every field, resolved from the request rather than kept.
     *
     * @param int[] $storagePageIds
     */
    protected function resolveScope(
        ServerRequestInterface $request,
        string $recordTable,
        array $storagePageIds,
        ?EditorFilter $editorFilter,
    ): FilterScope {
        return new FilterScope(
            $recordTable,
            $storagePageIds,
            $editorFilter,
            [
                CategoryAnchorSetting::CategoryParent->name => $this->anchors->categoryParent($request),
                CategoryAnchorSetting::KeywordParent->name => $this->anchors->keywordParent($request),
            ],
            $this->resolveSitePageIds($request)
        );
    }

    /**
     * Empty when the request carries no site, which offers nothing rather than
     * offering the whole instance.
     *
     * @return int[]
     */
    protected function resolveSitePageIds(ServerRequestInterface $request): array
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return [];
        }

        return $this->sitePageIds->forRootPage($site->getRootPageId());
    }

    /**
     * The provider is asked whether it reads this field's shape, so a shape
     * this class has never heard of is served by whichever provider ships with
     * it. A field no provider claims offers nothing rather than failing: the
     * mask stays usable, and its other filters keep working.
     */
    protected function buildField(FilterFieldDefinition $field, FilterScope $scope): FilterOptions
    {
        foreach (array_keys($this->providers->getProvidedServices()) as $id) {
            $provider = $this->providers->get($id);
            if ($provider instanceof FilterOptionProvider && $provider->supports($field)) {
                return $provider->provide($field, $scope);
            }
        }

        return new FilterOptions($field->getName(), []);
    }
}
