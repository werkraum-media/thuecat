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
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;

/**
 * The sys_category anchors a frontend request filters against.
 *
 * The import resolves anchors per import configuration; a request has no such
 * configuration, only a site. Both read the same settings under the same target,
 * so a filter offers exactly the tree its import writes.
 *
 * Places are always the ThueCat target: the event target's anchors belong to
 * event plugins, which resolve them on their own side.
 */
#[Autoconfigure(public: true)]
class FrontendCategoryAnchors
{
    public function __construct(
        private readonly CategoryAnchorResolver $resolver
    ) {
    }

    public function categoryParent(ServerRequestInterface $request): int
    {
        return $this->resolve($request, CategoryAnchorSetting::CategoryParent);
    }

    public function keywordParent(ServerRequestInterface $request): int
    {
        return $this->resolve($request, CategoryAnchorSetting::KeywordParent);
    }

    /**
     * 0 when the request carries no site, which is what an unconfigured anchor
     * yields too: the filter offers nothing rather than the whole tree.
     */
    private function resolve(ServerRequestInterface $request, CategoryAnchorSetting $setting): int
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return 0;
        }

        return $this->resolver->resolve($setting, $site, ImportTarget::Thuecat);
    }
}
