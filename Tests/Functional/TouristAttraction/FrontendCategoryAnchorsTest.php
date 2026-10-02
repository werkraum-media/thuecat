<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Service\FrontendCategoryAnchors;

// Isolates the one link the rendered mask depends on: the site settings the
// import writes against must be readable from a frontend request.
class FrontendCategoryAnchorsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForList.php';
    }

    #[Test]
    public function readsBothAnchorsFromTheSiteSettings(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByPageId(1);
        $request = (new ServerRequest())->withAttribute('site', $site);

        $anchors = $this->get(FrontendCategoryAnchors::class);

        self::assertSame(300, $anchors->categoryParent($request, 'tx_thuecat_tourist_attraction'));
        self::assertSame(500, $anchors->keywordParent($request, 'tx_thuecat_tourist_attraction'));
    }

    #[Test]
    public function aTrailFilterReadsTheTrailsAnchor(): void
    {
        $request = $this->requestForSiteSettings([
            'thuecat' => ['keywords' => ['parent' => 500, 'storagePid' => 11]],
            'trails' => ['keywords' => ['parent' => 600, 'storagePid' => 11]],
        ]);

        self::assertSame(600, $this->get(FrontendCategoryAnchors::class)->keywordParent($request, 'tx_thuecat_trail'));
    }

    #[Test]
    public function aTrailFilterWithoutTrailSettingsReadsTheThuecatAnchor(): void
    {
        $request = $this->requestForSiteSettings([
            'thuecat' => ['keywords' => ['parent' => 500, 'storagePid' => 11]],
        ]);

        self::assertSame(500, $this->get(FrontendCategoryAnchors::class)->keywordParent($request, 'tx_thuecat_trail'));
    }

    #[Test]
    public function anAttractionFilterIgnoresTheTrailsAnchor(): void
    {
        $request = $this->requestForSiteSettings([
            'thuecat' => ['keywords' => ['parent' => 500, 'storagePid' => 11]],
            'trails' => ['keywords' => ['parent' => 600, 'storagePid' => 11]],
        ]);

        self::assertSame(
            500,
            $this->get(FrontendCategoryAnchors::class)->keywordParent($request, 'tx_thuecat_tourist_attraction')
        );
    }

    /**
     * A site built in memory: the shared example site serves every frontend
     * test and stays free of trail settings.
     *
     * @param array<string, mixed> $import
     */
    private function requestForSiteSettings(array $import): ServerRequest
    {
        $site = new Site('anchors', 1, ['settings' => ['import' => $import]]);

        return (new ServerRequest())->withAttribute('site', $site);
    }
}
