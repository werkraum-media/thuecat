<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\DependencyInjection;

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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry;

class UrlProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $registry = $container->findDefinition(Registry::class);

        foreach ($container->findTaggedServiceIds('thuecat.urlprovider') as $id => $tags) {
            $definition = $container->findDefinition($id);
            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            // Services that implement MyCustomInterface need to be public,
            // to be lazy loadable by the registry via $container->get()
            // $container->findDefinition($id)->setPublic(true);
            $registry->addMethodCall('registerProvider', [$definition]);
        }
    }
}
