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

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

abstract class Base extends AbstractEntity
{
    /**
     * Name this record is assigned under in its own item template. Optional;
     * defaults to the lower-cased model name.
     */
    public const TEMPLATE_VARIABLE_NAME = '';

    protected string $title = '';

    protected string $description = '';

    protected ?FileReference $mainImage = null;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $mediaFiles;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $keywords;

    public function initializeObject(): void
    {
        $this->mediaFiles = new ObjectStorage();
        $this->keywords = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMainImage(): ?FileReference
    {
        return $this->mainImage;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMediaFiles(): ObjectStorage
    {
        return $this->mediaFiles;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getKeywords(): ObjectStorage
    {
        return $this->keywords;
    }
}
