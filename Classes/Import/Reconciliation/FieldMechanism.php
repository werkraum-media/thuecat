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

namespace WerkraumMedia\ThueCat\Import\Reconciliation;

/**
 * How a field upstream no longer supplies is removed from a root.
 */
enum FieldMechanism
{
    /** The column is written empty for its type. */
    case Scalar;

    /** The submitted list is the supplied set plus every related record the import did not create. */
    case RelationReplace;

    /** Stored rows carrying a remote_id that are not supplied are deleted. */
    case ExplicitDelete;

    /** The column is written empty, the record it pointed at stays. */
    case ReferenceWrite;
}
