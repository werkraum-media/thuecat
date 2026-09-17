<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

class TrailMetaTagsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForShow.php';
    }

    #[Test]
    public function emitsKeywordsMetaTagFromRelatedCategories(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="Themenweg, Fahrradfreundlich"#',
            $body
        );
    }

    #[Test]
    public function emitsNoKeywordsMetaTagWithoutRelations(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '20');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }

    #[Test]
    public function keywordsMetaTagUsesTranslatedTitles(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21', 10, 1);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="themed trail, bicycle friendly"#',
            $body
        );
    }
}
