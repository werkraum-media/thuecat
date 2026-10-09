<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Organisation extends AbstractEntity
{
    /**
     * @var ObjectStorage<Town>
     */
    protected ObjectStorage $managesTowns;

    /**
     * @var ObjectStorage<TouristInformation>
     */
    protected ObjectStorage $managesTouristInformation;

    public function getManagesTowns(): ObjectStorage
    {
        return $this->managesTowns;
    }

    public function getManagesTouristInformation(): ObjectStorage
    {
        return $this->managesTouristInformation;
    }

    public function getTableName(): string
    {
        return 'tx_thuecat_organisation';
    }
}
