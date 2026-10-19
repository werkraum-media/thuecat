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

/**
 * Enters every request into the run's ledger. Requests and responses pass
 * through unchanged.
 */
class RequestCountingClient implements ImportHttpClient
{
    public function __construct(
        protected readonly ImportHttpClient $client,
        protected readonly RequestLedger $ledger,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = null;
        try {
            return $response = $this->client->sendRequest($request);
        } finally {
            $this->ledger->record($request, $response);
        }
    }
}
