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

namespace WerkraumMedia\ThueCat\Import;

use RuntimeException;

/**
 * Thrown when the configured storagePid does not belong to any site. The whole
 * import depends on the storagePid's site (languages, page scope), so this is a
 * fatal misconfiguration regardless of category settings.
 */
final class StoragePidConfigurationException extends RuntimeException
{
}