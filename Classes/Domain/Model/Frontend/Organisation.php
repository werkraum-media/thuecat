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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\Events\Domain\Model\Event;

/**
 * An organisation as the frontend sees it: title and description via Base.
 * Not a Place — upstream puts organisations behind schema:containedInPlace,
 * but an organisation contains a record administratively, it is not a location.
 */
class Organisation extends Base
{
    /**
     * @var ObjectStorage<Address>
     */
    protected ObjectStorage $addressInline;

    /**
     * @var ObjectStorage<Event>
     */
    protected ObjectStorage $managesEvent;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->addressInline = new ObjectStorage();
        $this->managesEvent = new ObjectStorage();
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
    public function getManagesEvent(): ObjectStorage
    {
        return $this->managesEvent;
    }
}
