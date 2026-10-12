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

namespace WerkraumMedia\ThueCat\Domain\DetailRecordKind;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes one detail record kind to DetailRecordKinds.
 *
 * Another package adds a kind by implementing this in an autoconfigured
 * service; nothing in this extension lists the kinds.
 */
#[AutoconfigureTag(self::TAG)]
interface DetailRecordKindProvider
{
    public const TAG = 'thuecat.detail_record_kind';

    public function getKind(): DetailRecordKind;
}
