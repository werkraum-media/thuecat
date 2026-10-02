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
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;

/**
 * The sys_category anchors a frontend request filters against.
 *
 * The filtered record table decides the scope, through the same chain the
 * import writes with, so a filter offers the tree its records were filed in.
 */
#[Autoconfigure(public: true)]
class FrontendCategoryAnchors
{
    public function __construct(
        private readonly CategoryAnchorResolver $resolver
    ) {
    }

    public function categoryParent(ServerRequestInterface $request, string $recordTable): int
    {
        return $this->resolve($request, $recordTable, AnchorKind::Category);
    }

    public function keywordParent(ServerRequestInterface $request, string $recordTable): int
    {
        return $this->resolve($request, $recordTable, AnchorKind::Keyword);
    }

    /**
     * 0 when the request carries no site, which is what an unconfigured anchor
     * yields too: the filter offers nothing rather than the whole tree.
     */
    private function resolve(ServerRequestInterface $request, string $recordTable, AnchorKind $kind): int
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return 0;
        }

        return $this->resolver->resolvePair($site, $recordTable, $kind)->parent;
    }
}
