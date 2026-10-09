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

use Error;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Resolver;
use WerkraumMedia\ThueCat\Import\ResolverContext;

// Raises an Error from inside the guarded try, where the catch takes only
// Exception. rekeyRowsAndInjectPid runs within it on every fetched reference.
final class ResolverThrowingErrorStub extends Resolver
{
    protected function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        ResolverContext $context,
        int $depth
    ): void {
        if ($depth > 0) {
            throw new Error('Simulated programming defect while resolving a reference');
        }
        parent::rekeyRowsAndInjectPid($payload, $context, $depth);
    }
}
