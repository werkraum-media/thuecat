<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

class TrailSelectedTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForSelected.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'TrailSelectedRecordsRendering.typoscript';
    }

    #[Test]
    public function showsOnlyEditorSelectedRecords(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // settings.selectedRecords = 3,1
        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringContainsString('Ilmtal-Radweg', $body);
        self::assertStringNotContainsString('Lutherweg Thüringen', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // settings.selectedRecords = 3,1
        self::assertLessThan(
            mb_strpos($body, 'Goethe-Erlebnisweg'),
            mb_strpos($body, 'Ilmtal-Radweg'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }
}
