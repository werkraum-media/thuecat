<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
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
