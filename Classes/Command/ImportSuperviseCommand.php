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

namespace WerkraumMedia\ThueCat\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WerkraumMedia\ThueCat\Import\Job\ImportSupervisor;
use WerkraumMedia\ThueCat\Import\MediaStagingSweep;

#[AsCommand(name: 'thuecat:import:supervise', description: 'Starts due imports, resumes broken ones and removes leftover media staging. Schedule it every minute.')]
final class ImportSuperviseCommand extends Command
{
    public function __construct(
        private readonly ImportSupervisor $importSupervisor,
        private readonly MediaStagingSweep $mediaStagingSweep,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->importSupervisor->supervise();
        $this->mediaStagingSweep->sweep();

        return self::SUCCESS;
    }
}
