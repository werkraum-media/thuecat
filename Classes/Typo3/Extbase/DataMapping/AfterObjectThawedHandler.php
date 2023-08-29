<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Typo3\Extbase\DataMapping;

use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Extbase\Event\Persistence\AfterObjectThawedEvent;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Media;

/**
 * Will extend mapped objects with further info.
 *
 * E.g. will add editorial images to media property.
 */
class AfterObjectThawedHandler
{
    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @var DataMapFactory
     */
    private $dataMapFactory;

    public function __construct(
        FileRepository $fileRepository,
        DataMapFactory $dataMapFactory
    ) {
        $this->fileRepository = $fileRepository;
        $this->dataMapFactory = $dataMapFactory;
    }

    public function __invoke(AfterObjectThawedEvent $event): void
    {
        $object = $event->getObject();
        $record = $event->getRecord();

        if (
            $object instanceof Base
            && ($record['editorial_images'] ?? 0) > 0
        ) {
            $this->attachEditorialImages($object);
        }
    }

    private function attachEditorialImages(Base $object): void
    {
        $uid = $object->getUid();
        if ($uid === null) {
            return;
        }

        $images = $this->fileRepository->findByRelation(
            $this->getTableNameForObject($object),
            'editorial_images',
            $uid
        );
        if ($images === []) {
            return;
        }

        $this->getMedia($object)->setEditorialImages($images);
    }

    private function getTableNameForObject(Base $object): string
    {
        return $this->dataMapFactory
            ->buildDataMap(get_class($object))
            ->getTableName()
        ;
    }

    private function getMedia(Base $object): Media
    {
        $media = $object->getMedia();

        if (!$media instanceof Media) {
            $media = new Media('');
            $object->_setProperty('media', $media);
        }

        return $media;
    }
}
