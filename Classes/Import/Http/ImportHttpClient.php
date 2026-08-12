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

namespace WerkraumMedia\ThueCat\Import\Http;

use Psr\Http\Client\ClientInterface;

/**
 * A client carrying the import's timeouts and retry policy.
 *
 * Import classes depend on this rather than on ClientInterface: the container
 * binds it to ImportClientFactory, so there is no wiring that yields the shared
 * unbounded client and nothing for a caller to remember to apply.
 */
interface ImportHttpClient extends ClientInterface
{
}
