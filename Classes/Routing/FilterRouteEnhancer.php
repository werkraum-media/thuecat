<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
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

namespace WerkraumMedia\ThueCat\Routing;

use TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer;

/**
 * An Extbase plugin enhancer whose filters are optional, labelled path
 * segments in a fixed order.
 *
 * `filters` lists each filter by its route variable `name`, the plugin
 * `argument` it carries and, optionally, the `label` variable of a static
 * segment in front of it. The aspects for both are configured as usual. Every
 * configured route gets one variant per combination of filters, the filters in
 * the listed order in front of the route's own path. Core's route sorting
 * then picks the variant that consumes the most arguments.
 */
class FilterRouteEnhancer extends ExtbasePluginEnhancer
{
    /**
     * @param array<mixed> $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $filters = is_array($configuration['filters'] ?? null) ? array_values($configuration['filters']) : [];
        $this->routesOfPlugin = $this->expandRoutes($this->routesOfPlugin, $filters);
    }

    /**
     * Variants with more filters come first, so a path is matched by the most
     * specific variant.
     *
     * @param array<mixed> $routes
     * @param list<mixed> $filters
     *
     * @return list<array<mixed>>
     */
    protected function expandRoutes(array $routes, array $filters): array
    {
        $combinations = range(0, (1 << count($filters)) - 1);
        usort($combinations, static fn (int $a, int $b): int => substr_count(decbin($b), '1') <=> substr_count(decbin($a), '1'));

        $variants = [];
        foreach ($routes as $route) {
            if (!is_array($route) || !is_string($route['routePath'] ?? null)) {
                continue;
            }
            foreach ($combinations as $combination) {
                $variants[] = $this->variantFor($route, $filters, $combination);
            }
        }

        return $variants;
    }

    /**
     * @param array<mixed> $route
     * @param list<mixed> $filters
     *
     * @return array<mixed>
     */
    protected function variantFor(array $route, array $filters, int $combination): array
    {
        $segments = [];
        $arguments = is_array($route['_arguments'] ?? null) ? $route['_arguments'] : [];
        foreach ($filters as $index => $filter) {
            if (($combination & (1 << $index)) === 0 || !is_array($filter) || !is_string($filter['name'] ?? null)) {
                continue;
            }
            $name = $filter['name'];
            if (is_string($filter['label'] ?? null)) {
                $segments[] = '{' . $filter['label'] . '}';
            }
            $segments[] = '{' . $name . '}';
            $arguments[$name] = is_string($filter['argument'] ?? null) ? $filter['argument'] : $name;
        }

        $routePath = is_string($route['routePath']) ? $route['routePath'] : '';
        $route['routePath'] = rtrim('/' . implode('/', $segments), '/') . '/' . ltrim($routePath, '/');
        $route['_arguments'] = $arguments;

        return $route;
    }
}
