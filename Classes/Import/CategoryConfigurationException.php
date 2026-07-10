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
 * Thrown when category mapping is enabled (any category field carries content)
 * but the configuration is unusable:
 * * only one of parent / storage is set
 * * storagePid maps to no site
 * * a category anchor lives outside the storagePid's site
 *
 * Both category fields empty means mapping is off and is never an error.
 */
final class CategoryConfigurationException extends RuntimeException
{
}
