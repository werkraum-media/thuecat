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

namespace WerkraumMedia\ThueCat\Import\Settings;

/**
 * The import's tunables. The case value is the name used in both
 * ext_conf_template.txt and the import configuration flexform, so
 * ImportSettings can walk both levels from the case alone.
 */
enum ImportSetting: string
{
    case ReadTimeout = 'readTimeout';
    case ConnectTimeout = 'connectTimeout';
    case MaxAttempts = 'maxAttempts';
    case MessageBudget = 'messageBudget';
    case FetchCacheLifetime = 'fetchCacheLifetime';

    /**
     * Seconds, except MaxAttempts (a count). The message budget bounds one
     * root, so it stays well below what a whole job may take.
     */
    public function default(): int
    {
        return match ($this) {
            self::ReadTimeout => 120,
            self::ConnectTimeout => 30,
            self::MaxAttempts => 3,
            self::MessageBudget => 300,
            self::FetchCacheLifetime => 900, // 15 min
        };
    }
}
