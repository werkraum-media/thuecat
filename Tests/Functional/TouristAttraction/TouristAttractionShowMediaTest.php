<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

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

    protected function getRenderingTypoScript(): string
    {
        return 'ShowRendering.typoscript';
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
        $result = $this->executeFrontendSubRequest($this->requestForAttraction('21'));
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        // Processed (cropped) image lands in fileadmin processed folder.
        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $body);
        self::assertStringContainsString('Foto: Main Author', $body);
    }

    #[Test]
    public function rendersGalleryFromFalMediaFiles(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->requestForAttraction('21'))->getBody();

        self::assertStringContainsString('<section class="gallery">', $body);
        self::assertStringContainsString('Foto: Gallery Author', $body);
    }

    #[Test]
    public function rendersEditorialImagesFromFal(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->requestForAttraction('21'))->getBody();

        // Native editorial_images FAL property; processed via the reference crop.
        self::assertStringContainsString('<section class="editorial">', $body);
        self::assertStringContainsString('fileadmin-thuecat/_processed_/', $body);
    }

    #[Test]
    public function omitsEditorialSectionWithoutFalRelation(): void
    {
        // Record 22 has the legacy blob but no editorial_images relation; editorial is
        // FAL-only (no blob fallback) so the section must not appear.
        $body = (string)$this->executeFrontendSubRequest($this->requestForAttraction('22'))->getBody();

        self::assertStringNotContainsString('<section class="editorial">', $body);
    }

    #[Test]
    public function fallsBackToLegacyMediaBlob(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->requestForAttraction('22'))->getBody();

        // No FAL relation -> blob path; raw url, no processing.
        self::assertStringContainsString('https://cms.thuecat.org/legacy-main/image', $body);
        self::assertStringContainsString('Legacy Main Author', $body);
        self::assertStringContainsString('<section class="gallery">', $body);
        self::assertStringContainsString('https://cms.thuecat.org/legacy-extra/image', $body);
    }

    #[Test]
    public function rendersWithoutAnyMedia(): void
    {
        $result = $this->executeFrontendSubRequest($this->requestForAttraction('23'));
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Attraktion ohne Medien', $body);
        // Neither FAL nor blob: no figure, no gallery, no editorial, no broken markup.
        self::assertStringNotContainsString('<figure>', $body);
        self::assertStringNotContainsString('class="gallery"', $body);
        self::assertStringNotContainsString('class="editorial"', $body);
    }

    private function requestForAttraction(string $attractionUid): InternalRequest
    {
        $queryParams = ['tx_thuecat_touristattractionshow' => ['attraction' => $attractionUid]];
        $cHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters(http_build_query($queryParams + ['id' => 10]))
        ;

        return (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams($queryParams + ['cHash' => $cHash])
        ;
    }
}
