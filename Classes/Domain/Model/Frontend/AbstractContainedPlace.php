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

/**
 * A place that belongs to towns, is managed by an organisation and sits inside other records.
 */
abstract class AbstractContainedPlace extends AbstractPlace
{
    /**
     * Upstream puts whatever contains a record behind schema:containedInPlace,
     * and a record can belong to more than one town (an airport serving two
     * cities). Both are sets, not single values.
     *
     * @var ObjectStorage<Town>
     */
    protected ObjectStorage $towns;

    protected ?Organisation $managedBy = null;

    /**
     * @var ObjectStorage<Organisation>
     */
    protected ObjectStorage $containedInOrganisation;

    /**
     * containedInPlace gets one relation per target table: Extbase resolves a
     * relation through a single concrete, table-mapped class (its only
     * polymorphism is a recordType column choosing a subclass WITHIN one
     * table), so "any place" cannot be one property. The resolver already
     * knows which table each reference imported into and routes accordingly.
     *
     * @var ObjectStorage<TouristAttraction>
     */
    protected ObjectStorage $containedInAttraction;

    /**
     * @var ObjectStorage<TouristInformation>
     */
    protected ObjectStorage $containedInTouristInformation;

    /**
     * @var ObjectStorage<ParkingFacility>
     */
    protected ObjectStorage $containedInParkingFacility;

    /**
     * @var ObjectStorage<Place>
     */
    protected ObjectStorage $containedInPlace;

    /**
     * @var ObjectStorage<Trail>
     */
    protected ObjectStorage $containedInTrail;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->towns = new ObjectStorage();
        $this->containedInOrganisation = new ObjectStorage();
        $this->containedInAttraction = new ObjectStorage();
        $this->containedInTouristInformation = new ObjectStorage();
        $this->containedInParkingFacility = new ObjectStorage();
        $this->containedInPlace = new ObjectStorage();
        $this->containedInTrail = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Town>
     */
    public function getTowns(): ObjectStorage
    {
        return $this->towns;
    }

    public function getManagedBy(): ?Organisation
    {
        return $this->managedBy;
    }

    /**
     * @return ObjectStorage<Organisation>
     */
    public function getContainedInOrganisation(): ObjectStorage
    {
        return $this->containedInOrganisation;
    }

    /**
     * @return ObjectStorage<TouristAttraction>
     */
    public function getContainedInAttraction(): ObjectStorage
    {
        return $this->containedInAttraction;
    }

    /**
     * @return ObjectStorage<TouristInformation>
     */
    public function getContainedInTouristInformation(): ObjectStorage
    {
        return $this->containedInTouristInformation;
    }

    /**
     * @return ObjectStorage<ParkingFacility>
     */
    public function getContainedInParkingFacility(): ObjectStorage
    {
        return $this->containedInParkingFacility;
    }

    /**
     * @return ObjectStorage<Place>
     */
    public function getContainedInPlace(): ObjectStorage
    {
        return $this->containedInPlace;
    }

    /**
     * Every containing place, whatever table it came from — the template wants
     * one list, the storage is split because Extbase types a relation per table.
     *
     * @return list<AbstractPlace>
     */
    public function getContainedInPlaces(): array
    {
        return array_merge(
            array_values(iterator_to_array($this->containedInAttraction)),
            array_values(iterator_to_array($this->containedInTouristInformation)),
            array_values(iterator_to_array($this->containedInParkingFacility)),
            array_values(iterator_to_array($this->containedInPlace))
        );
    }

    /**
     * @return ObjectStorage<Trail>
     */
    public function getContainedInTrail(): ObjectStorage
    {
        return $this->containedInTrail;
    }
}
