<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

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

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory as Typo3RequestFactory;
use TYPO3\CMS\Core\Http\Uri;

class RequestFactory extends Typo3RequestFactory
{
    /**
     * @var ExtensionConfiguration
     */
    private $extensionConfiguration;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration
    ) {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        $uri = new Uri((string) $uri);

        $query = [];
        parse_str($uri->getQuery(), $query);
        $query = array_merge($query, [
            'format' => 'jsonld',
        ]);

        try {
            $query['api_key'] = $this->extensionConfiguration->get('thuecat', 'apiKey');
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            // Nothing todo, not configured, don't add.
        }

        $uri = $uri->withQuery(http_build_query($query));

        return parent::createRequest($method, $uri);
    }
}
