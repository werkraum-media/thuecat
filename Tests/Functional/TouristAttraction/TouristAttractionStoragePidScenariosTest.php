<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * storagePid (CE pages) and filter (CE flexform) each restrict independently;
 * storagePid only when actually set, else extbase's pid 0 would hide everything.
 * Records: Stadtmuseum Erfurt (pid 11, town 1), Goethehaus Weimar (pid 11,
 * town 2), Other Pid Erfurt (pid 12, town 1).
 */
class TouristAttractionStoragePidScenariosTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForStoragePidScenarios.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'StoragePidScenariosRendering.typoscript';
    }

    #[Test]
    public function storagePidWithoutFilterBehavesLikeTheListPlugin(): void
    {
        // pages=11, no filter -> every pid 11 record, nothing from pid 12.
        $body = $this->render(20);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Other Pid Erfurt', $body);
    }

    #[Test]
    public function noStoragePidAndNoFilterRendersEverything(): void
    {
        // pages empty, no filter -> records from any pid, unrestricted.
        $body = $this->render(21);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringContainsString('Other Pid Erfurt', $body);
    }

    #[Test]
    public function storagePidWithFilterLimitsByBoth(): void
    {
        // pages=11 AND towns=1 -> Erfurt on pid 11 only.
        $body = $this->render(22);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Other Pid Erfurt', $body);
    }

    #[Test]
    public function filterWithoutStoragePidIgnoresThePid(): void
    {
        // towns=1, no pages -> every town 1 record, any pid.
        $body = $this->render(23);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Other Pid Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    private function render(int $pageId): string
    {
        $request = (new InternalRequest())->withPageId($pageId);
        return (string)$this->executeFrontendSubRequest($request)->getBody();
    }
}
