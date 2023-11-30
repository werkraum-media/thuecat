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
     * @var string
     */
    private $serialized;

    /**
     * @var array[]
     */
    private $data;

    /**
     * @var FileReference[]
     */
    protected $editorialImages = [];

    public function __construct(string $serialized)
    {
        $this->serialized = $serialized;
        $data = json_decode($serialized, true);
        $this->data = $this->prepareData(is_array($data) ? $data : []);
    }

    public function getMainImage(): array
    {
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

    public function getImages(): array
    {
        return array_filter($this->data, function (array $media): bool {
            return $media['type'] === 'image';
        });
    }

    public function getExtraImages(): array
    {
        return array_filter($this->data, function (array $media): bool {
            return $media['type'] === 'image'
                && $media['mainImage'] === false;
        });
    }

    public function getAllImages(): array
    {
        return array_merge($this->getEditorialImages(), $this->getImages());
    }

    /**
     * @return FileReference[]
     */
    public function getEditorialImages(): array
    {
        return $this->editorialImages;
    }

    /**
     * @internal Only used to set the values while mapping objects.
     *
     * @see: AfterObjectThawedHandler
     */
    public function setEditorialImages(array $images): void
    {
        $this->editorialImages = $images;
    }

    public function __toString(): string
    {
        return $this->serialized;
    }

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
