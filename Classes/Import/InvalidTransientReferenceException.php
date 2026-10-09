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

namespace WerkraumMedia\ThueCat\Import;

use RuntimeException;

/**
 * Thrown when the resolver encounters a transient reference that is neither
 * an existing uid nor a NEW placeholder nor a fetchable URL. This means the
 * parser wrote something unexpected into a transient bucket — a bug, not a
 * recoverable state, so we refuse to silently drop the reference.
 */
final class InvalidTransientReferenceException extends RuntimeException
{
}
