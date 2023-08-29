<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

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

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileReference;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Media;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Model\Frontend\Media
 */
class MediaTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new Media('[]');

        self::assertInstanceOf(Media::class, $subject);
    }

    /**
     * @test
     */
    public function returnsMainImageIfPresent(): void
    {
        $subject = new Media('[{"mainImage":false,"type":"image","title":"Erfurt-Dom-und-Severikirche.jpg"},{"mainImage":true,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg"}]');

        self::assertSame([
            'mainImage' => true,
            'type' => 'image',
            'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
        ], $subject->getMainImage());
    }

    /**
     * @test
     */
    public function returnsEmptyArrayAsMainImageFallback(): void
    {
        $subject = new Media('[]');

        self::assertSame([], $subject->getMainImage());
    }

    /**
     * @test
     */
    public function returnsImagesAsArray(): void
    {
        $subject = new Media('[{"mainImage":false,"type":"image","title":"Erfurt-Dom-und-Severikirche.jpg"},{"mainImage":true,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg"}]');

        self::assertSame([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
            ],
            [
                'mainImage' => true,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
            ],
        ], $subject->getImages());
    }

    /**
     * @test
     */
    public function returnsExtraImagesAsArray(): void
    {
        $subject = new Media('[{"mainImage":false,"type":"image","title":"Erfurt-Dom-und-Severikirche.jpg"},{"mainImage":true,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg"}]');

        self::assertSame([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
            ],
        ], $subject->getExtraImages());
    }

    /**
     * @test
     */
    public function doesNotAddCopyrightAuthorIfItDoesntExist(): void
    {
        $subject = new Media(json_encode([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
            ],
        ]) ?: '');

        self::assertArrayNotHasKey(
            'copyrightAuthor',
            $subject->getExtraImages()[0]
        );
        self::assertArrayNotHasKey(
            'copyrightAuthor',
            $subject->getExtraImages()[1]
        );
    }

    /**
     * @test
     */
    public function addsCopyrightAuthorFromLicenseAuthor(): void
    {
        $subject = new Media(json_encode([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
                'license' => [
                    'author' => 'Full Name 1',
                ],
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                'license' => [
                    'author' => 'Full Name 2',
                ],
            ],
        ]) ?: '');

        self::assertSame(
            'Full Name 1',
            $subject->getExtraImages()[0]['copyrightAuthor']
        );
        self::assertSame(
            'Full Name 2',
            $subject->getExtraImages()[1]['copyrightAuthor']
        );
    }

    /**
     * @test
     */
    public function addsCopyrightAuthorFromAuthor(): void
    {
        $subject = new Media(json_encode([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
                'author' => 'Full Name 1',
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                'author' => 'Full Name 2',
            ],
        ]) ?: '');

        self::assertSame(
            'Full Name 1',
            $subject->getExtraImages()[0]['copyrightAuthor']
        );
        self::assertSame(
            'Full Name 2',
            $subject->getExtraImages()[1]['copyrightAuthor']
        );
    }

    /**
     * @test
     */
    public function addsCopyrightAuthorFromAuthorWithHigherPrio(): void
    {
        $subject = new Media(json_encode([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
                'author' => 'Full Name 1',
                'license' => [
                    'author' => 'Full Name 1 license',
                ],
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                'author' => 'Full Name 2',
                'license' => [
                    'author' => 'Full Name 2 license',
                ],
            ],
        ]) ?: '');

        self::assertSame(
            'Full Name 1',
            $subject->getExtraImages()[0]['copyrightAuthor']
        );
        self::assertSame(
            'Full Name 2',
            $subject->getExtraImages()[1]['copyrightAuthor']
        );
    }

    /**
     * @test
     */
    public function returnsEmptyArrayAsDefaultForEditorialImages(): void
    {
        $subject = new Media('');
        self::assertSame(
            [],
            $subject->getEditorialImages()
        );
    }

    /**
     * @test
     */
    public function returnsSetEditorialImages(): void
    {
        $subject = new Media('');
        $reference1 = $this->createStub(FileReference::class);
        $reference2 = $this->createStub(FileReference::class);
        $subject->setEditorialImages([
            $reference1,
            $reference2,
        ]);

        $images = $subject->getEditorialImages();

        self::assertCount(2, $images);
        self::assertSame($reference1, $images[0]);
        self::assertSame($reference2, $images[1]);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayAsDefaultForAllImages(): void
    {
        $subject = new Media('');
        self::assertSame(
            [],
            $subject->getAllImages()
        );
    }

    /**
     * @test
     */
    public function returnsAllImages(): void
    {
        $subject = new Media(json_encode([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
                'author' => 'Full Name 1',
                'license' => [
                    'author' => 'Full Name 1 license',
                ],
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                'author' => 'Full Name 2',
                'license' => [
                    'author' => 'Full Name 2 license',
                ],
            ],
        ]) ?: '');
        $reference1 = $this->createStub(FileReference::class);
        $reference2 = $this->createStub(FileReference::class);
        $subject->setEditorialImages([
            $reference1,
            $reference2,
        ]);

        self::assertSame(
            $reference1,
            $subject->getAllImages()[0]
        );
        self::assertSame(
            $reference2,
            $subject->getAllImages()[1]
        );
        self::assertSame(
            'Full Name 1',
            $subject->getAllImages()[2]['copyrightAuthor']
        );
        self::assertSame(
            'Full Name 2',
            $subject->getAllImages()[3]['copyrightAuthor']
        );
    }
}
