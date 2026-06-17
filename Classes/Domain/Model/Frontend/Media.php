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

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Type\TypeInterface;

class Media implements TypeInterface
{
    /**
     * @var array[]
     */
    private readonly array $data;

    /**
     * @var FileReference[]
     */
    protected array $editorialImages = [];

    public function __construct(
        private readonly string $serialized
    ) {
        $data = json_decode($serialized, true);
        $this->data = $this->prepareData(is_array($data) ? $data : []);
    }

    /**
     * @deprecated Legacy JSON-blob media. Use the FAL field main_image (Base::getMainImage())
     *             instead; re-run the import to populate it. Removed in the next major.
     */
    public function getMainImage(): array
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Media::getMainImage() reads the deprecated'
            . ' JSON-blob media. Use the FAL field main_image (re-run the import). Removed in the next major.',
            E_USER_DEPRECATED
        );

        foreach ($this->data as $media) {
            if (
                $media['type'] === 'image'
                && $media['mainImage'] === true
            ) {
                return $media;
            }
        }

        return [];
    }

    /**
     * @return array[]
     *
     * @deprecated Legacy JSON-blob media. Use the FAL field media_files (Base::getMediaFiles())
     *             instead; re-run the import to populate it. Removed in the next major.
     */
    public function getImages(): array
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Media::getImages() reads the deprecated'
            . ' JSON-blob media. Use the FAL field media_files (re-run the import). Removed in the next major.',
            E_USER_DEPRECATED
        );

        return array_filter($this->data, function (array $media): bool {
            return $media['type'] === 'image';
        });
    }

    /**
     * @return array[]
     *
     * @deprecated Legacy JSON-blob media. Use the FAL field media_files (Base::getMediaFiles())
     *             instead; re-run the import to populate it. Removed in the next major.
     */
    public function getExtraImages(): array
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Media::getExtraImages() reads the deprecated'
            . ' JSON-blob media. Use the FAL field media_files (re-run the import). Removed in the next major.',
            E_USER_DEPRECATED
        );

        return array_filter($this->data, function (array $media): bool {
            return $media['type'] === 'image'
                && $media['mainImage'] === false;
        });
    }

    /**
     * @return array<FileReference|array>
     *
     * @deprecated Legacy JSON-blob media. Use the FAL fields editorial_images + media_files
     *             (Base::getEditorialImages() / getMediaFiles()) instead. Removed in the next major.
     */
    public function getAllImages(): array
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Media::getAllImages() reads the deprecated'
            . ' JSON-blob media. Use the FAL fields editorial_images + media_files. Removed in the next major.',
            E_USER_DEPRECATED
        );

        // Inlined (not via getImages()/getEditorialImages()) to avoid stacking their warnings.
        $images = array_filter($this->data, static function (array $media): bool {
            return $media['type'] === 'image';
        });

        return array_merge($this->editorialImages, $images);
    }

    /**
     * @return FileReference[]
     *
     * @deprecated Legacy carrier. Use the FAL field editorial_images (Base::getEditorialImages())
     *             instead. Removed in the next major.
     */
    public function getEditorialImages(): array
    {
        trigger_error(
            'WerkraumMedia\ThueCat\Domain\Model\Frontend\Media::getEditorialImages() is the deprecated'
            . ' editorial-images carrier. Use the FAL field editorial_images (Base::getEditorialImages()).'
            . ' Removed in the next major.',
            E_USER_DEPRECATED
        );

        return $this->editorialImages;
    }

    /**
     * @internal Only used to set the values while mapping objects.
     *
     * @see: AfterObjectThawedHandler
     *
     * @param FileReference[] $images
     */
    public function setEditorialImages(array $images): void
    {
        $this->editorialImages = $images;
    }

    public function __toString(): string
    {
        return $this->serialized;
    }

    /**
     * @return array[]
     */
    private function prepareData(array $data): array
    {
        return array_map(function (array $media) {
            $copyrightAuthor = $media['author'] ?? $media['license']['author'] ?? '';

            if ($copyrightAuthor) {
                $media['copyrightAuthor'] = $copyrightAuthor;
            }
            return $media;
        }, $data);
    }
}
