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

namespace WerkraumMedia\ThueCat\Import\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Writes every request a host refused for want of a valid key to the TYPO3
 * log, so a developer can check each host's credential rules by running the
 * imports once. The key itself is never written. Requests and responses pass
 * through unchanged.
 */
class DeniedRequestLoggingClient implements ImportHttpClient
{
    protected const DENIED_STATUSES = [401, 403];

    public function __construct(
        protected readonly ImportHttpClient $client,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);
        if (!in_array($response->getStatusCode(), self::DENIED_STATUSES, true)) {
            return $response;
        }

        $this->logger->warning(
            'Import request {method} {url} was denied with {status}; credential sent: {credential}.',
            [
                'method' => $request->getMethod(),
                'url' => RedactedUrl::of($request->getUri()),
                'status' => $response->getStatusCode(),
                'credential' => $this->credentialForm($request),
            ]
        );

        return $response;
    }

    protected function credentialForm(RequestInterface $request): string
    {
        if ($request->hasHeader('Authorization')) {
            return 'header';
        }
        parse_str($request->getUri()->getQuery(), $query);

        return isset($query['api_key']) ? 'query' : 'none';
    }
}
