<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

/**
 * Renders the Show template with the FAL media fields a trail carries:
 * main_image, media_files and the trail-specific logo.
 *
 * A real local storage holding a real image is set up so core can process the
 * cropped references; the crop on the sys_file_reference drives the processing.
 */
class TrailShowMediaTest extends AbstractFrontendTestCase
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
        return 'TrailsForShowMedia.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

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
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $section = $this->renderedSection($request, 'relation', 'mainImage');

        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $section);
        self::assertStringContainsString('Foto: Main Author', $section);
    }

    #[Test]
    public function rendersGalleryFromFalMediaFiles(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $section = $this->renderedSection($request, 'relation', 'mediaFiles');

        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $section);
        self::assertStringContainsString('Foto: Gallery Author', $section);
    }

    #[Test]
    public function rendersLogoFromFal(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $section = $this->renderedSection($request, 'relation', 'logo');

        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $section);
    }

    // Square 20x20 source, 2:1 crop: an uncropped render would be 200 high.
    #[Test]
    public function appliesTheEditorsCropToTheProcessedImage(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $section = $this->renderedSection($request, 'relation', 'mainImage');

        self::assertMatchesRegularExpression('#<img[^>]+width="200"#', $section);
        self::assertMatchesRegularExpression('#<img[^>]+height="100"#', $section);
    }

    #[Test]
    public function rendersWithoutAnyMedia(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '22');

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Weg ohne Medien', $body);
        self::assertStringNotContainsString('data-relation="mainImage"', $body);
        self::assertStringNotContainsString('data-relation="mediaFiles"', $body);
        self::assertStringNotContainsString('data-relation="logo"', $body);
    }
}
