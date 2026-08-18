<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
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

        self::assertSame(300, $anchors->categoryParent($request));
        self::assertSame(500, $anchors->keywordParent($request));
    }
}
