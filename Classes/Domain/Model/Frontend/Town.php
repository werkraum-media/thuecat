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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\Events\Domain\Model\Event;

class Town extends Base
{
    /**
     * @var ObjectStorage<Address>
     */
    protected ObjectStorage $addressInline;

    /**
     * @var ObjectStorage<Event>
     */
    protected ObjectStorage $hostsEvents;

    protected ?Organisation $managedBy = null;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->addressInline = new ObjectStorage();
        $this->hostsEvents = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function getAddressInline(): ObjectStorage
    {
        return $this->addressInline;
    }

    public function getFirstAddress(): ?Address
    {
        foreach ($this->addressInline as $address) {
            return $address;
        }

        return null;
    }

    /**
     * @return ObjectStorage<Event>
     */
    public function getHostsEvents(): ObjectStorage
    {
        return $this->hostsEvents;
    }

    public function getManagedBy(): ?Organisation
    {
        return $this->managedBy;
    }
}
