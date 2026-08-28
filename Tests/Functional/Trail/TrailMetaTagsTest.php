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
        self::markTestSkipped(
            'Translated records are not resolved by this suite\'s frontend sub-requests: the /en request renders'
            . ' English chrome but keeps the default-language trail, so the relation never reaches categories'
            . ' 503/504. Expected content="themed trail, bicycle friendly" from trail 22.'
        );
    }
}
