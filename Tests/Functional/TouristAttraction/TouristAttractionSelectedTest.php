<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionSelectedTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSelected.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'SelectedRecordsRendering.typoscript';
    }

    #[Test]
    public function showsOnlyEditorSelectedRecords(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // settings.selectedRecords = 3,1 -> Goethehaus (3) and Stadtmuseum (1)
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // settings.selectedRecords = 3,1 -> Goethehaus must appear before Stadtmuseum
        self::assertLessThan(
            mb_strpos($body, 'Stadtmuseum Erfurt'),
            mb_strpos($body, 'Goethehaus Weimar'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }
}
