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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use RuntimeException;

/**
 * Thrown by GuzzleClientFaker when production code fetches a URL the test
 * did not stage (or staged the wrong number of times). Distinct exception
 * class so production catches that swallow generic exceptions don't hide
 * test-side wiring mistakes.
 */
final class UnexpectedFetchException extends RuntimeException
{
}
