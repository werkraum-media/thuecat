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

namespace WerkraumMedia\ThueCat\Frontend\MetaInformation;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;

/**
 * Connects a place detail view to TYPO3's meta information APIs, so the detail
 * view has a single place to reach for rather than touching those APIs itself.
 *
 * Keyword meta tags are sourced from the record's related keyword categories.
 * A place carrying no keyword relations emits no keyword meta tag.
 */
#[Autoconfigure(public: true)]
class TouristAttractionMetaInformationService
{
    public function __construct(
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry
    ) {
    }

    public function setAttraction(TouristAttraction $attraction): void
    {
        $this->setKeywords($attraction);
    }

    protected function setKeywords(TouristAttraction $attraction): void
    {
        $titles = [];
        foreach ($attraction->getKeywords() as $keyword) {
            $title = $keyword->getTitle();
            if ($title !== '') {
                $titles[] = $title;
            }
        }

        if ($titles === []) {
            return;
        }

        $this->metaTagManagerRegistry
            ->getManagerForProperty('keywords')
            ->addProperty('keywords', implode(', ', $titles))
        ;
    }
}
