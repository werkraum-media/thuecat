<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Service\SearchFilterOptionsService;

/**
 * One scope is resolved per request and every field is built against it, so a
 * filter cannot decide its own scoping and nothing a request resolved outlives
 * it.
 */
class SearchFilterOptionsServiceTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilter.php';
    }

    #[Test]
    public function offersOneOptionSetPerTaggedField(): void
    {
        $options = $this->get(SearchFilterOptionsService::class)->build($this->request(), 'tx_thuecat_tourist_attraction', [11]);

        $names = array_map(
            static fn (FilterOptions $set): string => $set->getName(),
            $options
        );
        sort($names);

        self::assertSame(['categories', 'keywords', 'towns'], $names);
    }

    #[Test]
    public function buildsEveryFieldAgainstTheSameScope(): void
    {
        $options = $this->get(SearchFilterOptionsService::class)->build($this->request(), 'tx_thuecat_tourist_attraction', [11]);

        // All three read the same storage, so each offers what page 11 holds.
        self::assertSame(['Erfurt', 'Weimar'], $this->titles($options['towns']));
        self::assertSame(['Gebäudetyp', 'Haus'], $this->titles($options['categories']));
        self::assertSame(['Ambiente', 'Hauptstadt'], $this->titles($options['keywords']));
    }

    #[Test]
    public function returnsTheSameTypeWithoutASiblingList(): void
    {
        $options = $this->get(SearchFilterOptionsService::class)->build($this->request(), 'tx_thuecat_tourist_attraction', []);

        self::assertContainsOnlyInstancesOf(FilterOptions::class, $options);
        self::assertArrayHasKey('towns', $options);
    }

    #[Test]
    public function twoSitesEachGetTheirOwnAnchors(): void
    {
        $service = $this->get(SearchFilterOptionsService::class);

        $configured = $service->build($this->request(), 'tx_thuecat_tourist_attraction', [11]);
        // Same service instance, a site whose anchors point nowhere. Anything
        // held from the first build would show up as the first site's tree.
        $foreign = $service->build($this->requestForAnchors(9000, 9001), 'tx_thuecat_tourist_attraction', [11]);

        self::assertSame(['Gebäudetyp', 'Haus'], $this->titles($configured['categories']));
        self::assertSame([], $foreign['categories']->getOptions());
    }

    #[Test]
    public function aSecondBuildIsScopedByItsOwnStoragePages(): void
    {
        $service = $this->get(SearchFilterOptionsService::class);

        $inStorage = $service->build($this->request(), 'tx_thuecat_tourist_attraction', [11]);
        $elsewhere = $service->build($this->request(), 'tx_thuecat_tourist_attraction', [10]);

        self::assertSame(['Erfurt', 'Weimar'], $this->titles($inStorage['towns']));
        self::assertSame([], $elsewhere['towns']->getOptions());
    }

    private function request(): ServerRequest
    {
        $site = $this->get(SiteFinder::class)->getSiteByPageId(1);

        return (new ServerRequest())->withAttribute('site', $site);
    }

    /**
     * A site of this instance carrying anchors of its own, so two builds can
     * differ without a second site configuration.
     */
    private function requestForAnchors(int $categoryParent, int $keywordParent): ServerRequest
    {
        $site = new Site('other', 1, [
            'base' => '/',
            'rootPageId' => 1,
            'languages' => [],
            'settings' => [
                'import' => [
                    'thuecat' => [
                        'category' => ['parent' => $categoryParent, 'storagePid' => 11],
                        'keywords' => ['parent' => $keywordParent, 'storagePid' => 11],
                    ],
                ],
            ],
        ]);

        return (new ServerRequest())->withAttribute('site', $site);
    }

    /**
     * @return list<string>
     */
    private function titles(FilterOptions $options): array
    {
        return array_values(array_map(
            static fn (FilterOption $option): string => $option->getTitle(),
            $options->getOptions()
        ));
    }
}
