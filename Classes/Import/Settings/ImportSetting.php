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
    case RunBudget = 'runBudget';
    case FetchCacheLifetime = 'fetchCacheLifetime';

    /**
     * Seconds, except MaxAttempts (a count). Generous while the import runs at
     * ~5s per event; a tighter budget would abort healthy large runs.
     */
    public function default(): int
    {
        return match ($this) {
            self::ReadTimeout => 120,
            self::ConnectTimeout => 30,
            self::MaxAttempts => 3,
            self::RunBudget => 86400,
            self::FetchCacheLifetime => 900,
        };
    }
}
