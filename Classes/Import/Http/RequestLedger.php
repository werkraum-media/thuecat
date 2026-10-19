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

use DateTimeImmutable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Records the position of every request in a run and the requests a host
 * answered with 429.
 */
class RequestLedger
{
    protected int $requests = 0;

    /**
     * @var list<array{url: string, host: string, time: string, ordinal: int}>
     */
    protected array $rateLimited = [];

    // Null: the request failed without a response, and still counts.
    public function record(RequestInterface $request, ?ResponseInterface $response): void
    {
        $this->requests++;
        if ($response?->getStatusCode() !== 429) {
            return;
        }

        $this->rateLimited[] = [
            'url' => RedactedUrl::of($request->getUri()),
            'host' => $request->getUri()->getHost(),
            'time' => (new DateTimeImmutable())->format(DATE_ATOM),
            'ordinal' => $this->requests,
        ];
    }

    /**
     * @return list<array{url: string, host: string, time: string, ordinal: int}>
     */
    public function rateLimited(): array
    {
        return $this->rateLimited;
    }

    public function reset(): void
    {
        $this->requests = 0;
        $this->rateLimited = [];
    }
}
