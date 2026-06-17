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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

abstract class Base extends AbstractEntity
{
    protected string $title = '';

    protected string $description = '';

    protected ?Media $media = null;

    protected ?FileReference $mainImage = null;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $mediaFiles;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $editorialImages;

    public function initializeObject(): void
    {
        $this->mediaFiles = new ObjectStorage();
        $this->editorialImages = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @deprecated Legacy JSON-blob media carrier. Use the FAL fields main_image / media_files /
     *             editorial_images (getMainImage() / getMediaFiles() / getEditorialImages())
     *             instead; re-run the import to populate them. Removed in the next major.
     */
    public function getMedia(): ?Media
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Base::getMedia() returns the deprecated'
            . ' JSON-blob media carrier. Use getMainImage() / getMediaFiles() / getEditorialImages()'
            . ' (re-run the import). Removed in the next major.',
            E_USER_DEPRECATED
        );

        return $this->media;
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
     * @return ObjectStorage<FileReference>
     */
    public function getEditorialImages(): ObjectStorage
    {
        return $this->editorialImages;
    }
}
