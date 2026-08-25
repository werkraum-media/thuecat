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

namespace WerkraumMedia\ThueCat\Import\SysCategory;

use WerkraumMedia\ThueCat\Import\ImportLogger;

/**
 * The strategy each kind of record places its categories by.
 *
 * Upstream classes sit under several roots at once — a theatre venue is a local
 * business and a tourist attraction both — so which parent to follow depends on
 * what the importing record is, not on the class. An event belongs under events;
 * a point of interest belongs under what a visitor came to see.
 *
 * A table with no entry keeps the deepest branch, which says the most about the
 * class when nothing else distinguishes the candidates.
 */
class ParentStrategies
{
    protected const PREFERRED_ROOTS = [
        'tx_thuecat_tourist_attraction' => ['schema:TouristAttraction', 'schema:Place'],
        'tx_events_domain_model_event' => ['schema:Event'],
    ];

    /** @var array<string, ParentStrategy> */
    protected array $strategies = [];

    public function __construct(
        protected readonly LongestChainStrategy $longestChain,
        protected readonly ImportLogger $importLogger
    ) {
    }

    public function forTable(string $ownerTable): ParentStrategy
    {
        if (!isset(self::PREFERRED_ROOTS[$ownerTable])) {
            return $this->longestChain;
        }

        return $this->strategies[$ownerTable] ??= new PreferredRootStrategy(
            'preferredRoot:' . implode('|', self::PREFERRED_ROOTS[$ownerTable]),
            self::PREFERRED_ROOTS[$ownerTable],
            $this->longestChain,
            $this->importLogger
        );
    }
}
