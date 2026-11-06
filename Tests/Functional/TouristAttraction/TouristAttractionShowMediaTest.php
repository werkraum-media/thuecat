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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

/**
 * Renders the Show template with the FAL-based media fields (main_image, media_files),
 * its legacy `media` JSON blob fallback, and the no-media case.
 *
 * A real local storage holding a real image is set up so core can process the cropped
 * references; the crop on the sys_file_reference drives the processing.
 */
class TouristAttractionShowMediaTest extends AbstractFrontendTestCase
{
    // The testing framework forces GFX.processor to GraphicsMagick, which is not
    // installed here; point it back at ImageMagick so f:image actually processes.
    protected array $configurationToUseInTestInstance = [
        'GFX' => [
            'processor' => 'ImageMagick',
        ],
    ];

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForShowMedia.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Fixtures reference storage 1 at "/thuecat/<file>"; create it with a real
        // image on disk so the cropped references resolve and get processed.
        $basePath = $this->instancePath . '/fileadmin-thuecat';
        GeneralUtility::mkdir_deep($basePath . '/thuecat');
        copy(
            __DIR__ . '/../Fixtures/Frontend/Images/attraction.jpg',
            $basePath . '/thuecat/image.jpg'
        );
        $this->get(StorageRepository::class)->createLocalStorage(
            'ThueCat test storage',
            $basePath,
            'absolute'
        );
    }

    #[Test]
    public function rendersMainImageFromFal(): void
    {
        $result = $this->executeFrontendSubRequest($this->attractionShowRequest('21'));
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        // Processed (cropped) image lands in fileadmin processed folder.
        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $body);
        self::assertStringContainsString('Foto: Main Author', $body);
    }

    // Square 20x20 source, 2:1 crop: an uncropped render would be 20x20.
    #[Test]
    public function appliesTheEditorsCropToTheProcessedImage(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('21'));

        self::assertMatchesRegularExpression('#<img[^>]+width="10"#', $body);
        self::assertMatchesRegularExpression('#<img[^>]+height="5"#', $body);
    }

    #[Test]
    public function rendersGalleryFromFalMediaFiles(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('21'));

        self::assertStringContainsString('<section class="gallery">', $body);
        self::assertStringContainsString('Foto: Gallery Author', $body);
    }

    #[Test]
    public function rendersEditorialImagesFromFal(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('21'));

        // Native editorial_images FAL property; processed via the reference crop.
        self::assertStringContainsString('<section class="editorial">', $body);
        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $body);
    }

    #[Test]
    public function omitsEditorialSectionWithoutFalRelation(): void
    {
        // Record 22 has the legacy blob but no editorial_images relation; editorial is
        // FAL-only (no blob fallback) so the section must not appear.
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('22'));

        self::assertStringNotContainsString('<section class="editorial">', $body);
    }

    #[Test]
    public function rendersTheLogoAndTheSubjectMediaOfAVenue(): void
    {
        $media = $this->renderedSection($this->attractionShowRequest('24'), 'venue', 'media');

        self::assertStringContainsString('Foto: Main Author', $media);
        self::assertStringContainsString('Foto: Gallery Author', $media);
        self::assertSame(2, substr_count($media, '<img '));
    }

    #[Test]
    public function rendersTheMediaOfAnInlineParkingFacility(): void
    {
        $parking = $this->renderedSection($this->attractionShowRequest('24'), 'venue', 'parkingFacilities');

        self::assertStringContainsString('Foto: Editorial Author', $parking);
        self::assertStringContainsString('Foto: Gallery Author', $parking);
        self::assertSame(2, substr_count($parking, '<img '));
    }

    #[Test]
    public function rendersTheMediaOfAMeetingRoom(): void
    {
        $details = $this->renderedSection($this->attractionShowRequest('24'), 'venue', 'meetingRoomDetails');

        self::assertStringContainsString('Foto: Main Author', $details);
        self::assertStringContainsString('Foto: Editorial Author', $details);
        self::assertSame(2, substr_count($details, '<img '));
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function imagesWithTheirMetadata(): iterable
    {
        yield 'main image' => ['21', 'attraction', 'images', 'Main image', 'Main image description'];
        yield 'gallery image' => ['21', 'attraction', 'images', 'Gallery image', 'Gallery image description'];
        yield 'editorial image' => ['21', 'attraction', 'images', 'Editorial image', 'Editorial image description'];
        yield 'venue logo' => ['24', 'venue', 'media', 'Logo', 'Logo description'];
        yield 'venue portal image' => ['24', 'venue', 'media', 'Portal image', 'Portal image description'];
        yield 'parking main image' => ['24', 'venue', 'parkingFacilities', 'Parking main image', 'Parking main image description'];
        yield 'parking gallery image' => ['24', 'venue', 'parkingFacilities', 'Parking gallery image', 'Parking gallery image description'];
        yield 'room main image' => ['24', 'venue', 'meetingRoomDetails', 'Room main image', 'Room main image description'];
        yield 'room gallery image' => ['24', 'venue', 'meetingRoomDetails', 'Room gallery image', 'Room gallery image description'];
    }

    #[Test]
    #[DataProvider('imagesWithTheirMetadata')]
    public function rendersTheMetadataOfAnImage(string $uid, string $attribute, string $section, string $title, string $description): void
    {
        $markup = $this->renderedSection($this->attractionShowRequest($uid), $attribute, $section);

        self::assertMatchesRegularExpression('#<img [^>]*alt="Alternative: ' . $title . '"#', $markup);
        // Title and description in one and the same caption.
        $inCaption = '(?:(?!</figcaption>).)*';
        self::assertMatchesRegularExpression('#<figcaption[^>]*>' . $inCaption . $title . $inCaption . $description . $inCaption . '</figcaption>#s', $markup);
    }

    #[Test]
    public function rendersWithoutAnyMedia(): void
    {
        $result = $this->executeFrontendSubRequest($this->attractionShowRequest('23'));
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Attraktion ohne Medien', $body);
        // Neither FAL nor blob: no figure, no gallery, no editorial, no broken markup.
        self::assertStringNotContainsString('<figure>', $body);
        self::assertStringNotContainsString('class="gallery"', $body);
        self::assertStringNotContainsString('class="editorial"', $body);
    }
}
