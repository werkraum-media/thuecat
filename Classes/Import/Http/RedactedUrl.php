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

use Psr\Http\Message\UriInterface;

/**
 * A request URL fit for a log: the old configuration types carry their key as
 * `api_key` in the query.
 */
final class RedactedUrl
{
    public static function of(UriInterface $uri): string
    {
        parse_str($uri->getQuery(), $query);
        if (isset($query['api_key'])) {
            $query['api_key'] = 'REDACTED';
        }

        return (string)$uri->withQuery(http_build_query($query));
    }
}
