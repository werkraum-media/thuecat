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

namespace WerkraumMedia\ThueCat\Service\FilterField;

/**
 * The town filter.
 *
 * Towns sit in a TCA select without MM, so a record carries its towns as a
 * comma-separated uid list and a record without a town carries 0.
 */
final class TownFilterField extends CommaColumnField
{
    public function __construct()
    {
        parent::__construct(
            name: 'towns',
            recordColumn: 'town',
            optionTable: 'tx_thuecat_town',
        );
    }
}
