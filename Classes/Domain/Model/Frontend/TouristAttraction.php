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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\Events\Domain\Model\Event;

class TouristAttraction extends AbstractContainedPlace
{
    /** Established template variable, shorter than the model name. */
    public const TEMPLATE_VARIABLE_NAME = 'attraction';

    protected string $slogan = '';

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    protected ?Offers $offers = null;

    /**
     * @var ObjectStorage<Event>
     */
    protected ObjectStorage $hostsEvents;

    protected string $startOfConstruction = '';

    protected string $museumService = '';

    protected string $architecturalStyle = '';

    /**
     * Necessary for Extbase/Symfony.
     *
     * @var string
     */
    protected string $digitalOffer = '';

    /**
     * Necessary for Extbase/Symfony.
     *
     * @var string
     */
    protected string $photography = '';

    protected string $petsAllowed = '';

    protected string $isAccessibleForFree = '';

    protected string $publicAccess = '';

    protected string $availableLanguages = '';

    protected string $type = 'attraction';

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->categories = new ObjectStorage();
        $this->hostsEvents = new ObjectStorage();
    }

    /**
     * Templates choose their output by it, e.g. the venue partials for 'meeting_venue'.
     */
    public function getRecordType(): string
    {
        return $this->type;
    }

    /**
     * The ThueCat type for the structured data, where schema.org has none.
     */
    public function getAdditionalType(): string
    {
        return '';
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
    * @return string[]
    */
    public function getSlogans(): array
    {
        return explode(',', $this->slogan);
    }

    public function getOffers(): ?Offers
    {
        return $this->offers;
    }

    /**
     * @return ObjectStorage<Event>
     */
    public function getHostsEvents(): ObjectStorage
    {
        return $this->hostsEvents;
    }

    /**
     * @return string[]
     */
    public function getAvailableLanguageMembers(): array
    {
        return GeneralUtility::trimExplode(',', $this->availableLanguages, true);
    }

    public function getStartOfConstruction(): string
    {
        return $this->startOfConstruction;
    }

    public function getMuseumServices(): array
    {
        return GeneralUtility::trimExplode(',', $this->museumService, true);
    }

    public function getArchitecturalStyles(): array
    {
        return GeneralUtility::trimExplode(',', $this->architecturalStyle, true);
    }

    public function getDigitalOffer(): array
    {
        return GeneralUtility::trimExplode(',', $this->digitalOffer, true);
    }

    public function getPhotography(): array
    {
        return GeneralUtility::trimExplode(',', $this->photography, true);
    }

    public function getPetsAllowed(): string
    {
        return $this->petsAllowed;
    }

    public function getIsAccessibleForFree(): string
    {
        return $this->isAccessibleForFree;
    }

    public function getPublicAccess(): string
    {
        return $this->publicAccess;
    }

    public function getSlogan(): string
    {
        return explode(',', $this->slogan)[0];
    }
}
