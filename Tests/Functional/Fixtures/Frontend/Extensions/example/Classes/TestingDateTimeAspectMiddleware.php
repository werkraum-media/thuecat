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

namespace WerkraumMedia\Example;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;

/**
 * Allows us to set a specific DateTimeAspect for requests within tests.
 */
final class TestingDateTimeAspectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $testingDateAspect = $request->getAttribute('testingDateAspect');
        if ($testingDateAspect instanceof DateTimeAspect) {
            $this->context->setAspect('date', $testingDateAspect);
        }

        return $handler->handle($request);
    }
}
