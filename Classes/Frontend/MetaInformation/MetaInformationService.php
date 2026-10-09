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

namespace WerkraumMedia\ThueCat\Frontend\MetaInformation;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;

/**
 * Connects a place detail view to TYPO3's meta information APIs, so the detail
 * view has a single place to reach for rather than touching those APIs itself.
 *
 * Keyword meta tags are sourced from the record's related keyword categories.
 * A place carrying no keyword relations emits no keyword meta tag.
 */
#[Autoconfigure(public: true)]
class MetaInformationService
{
    public function __construct(
        protected readonly MetaTagManagerRegistry $metaTagManagerRegistry
    ) {
    }

    public function setObject(Base $object): void
    {
        $this->setKeywords($object);
    }

    protected function setKeywords(Base $object): void
    {
        $titles = [];
        foreach ($object->getKeywords() as $keyword) {
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
