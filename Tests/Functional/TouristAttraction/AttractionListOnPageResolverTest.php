<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The resolver is exercised through the rendered search form: a list on the page
 * makes the form stay (action -> same page) and a filtered list hides+locks its
 * preset fields.
 */
class AttractionListOnPageResolverTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForResolver.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'ResolverRendering.typoscript';
    }

    #[Test]
    public function filteredListOnPageHidesTheLockedTownAndKeepsFormOnPage(): void
    {
        $body = $this->render(10);

        self::assertMatchesRegularExpression(
            '#<input type="hidden" name="tx_thuecat_touristattractionlist\[demand\]\[towns\]\[\d*\]" value="1"#',
            $body
        );
        self::assertStringNotContainsString('<select', $body);
        self::assertMatchesRegularExpression('#<form[^>]+action="[^"]*/filtered-combined/[^"]*"#', $body);
    }

    #[Test]
    public function plainListOnPageKeepsTownSelectableAndFormOnPage(): void
    {
        $body = $this->render(20);

        self::assertStringContainsString('<select', $body);
        self::assertMatchesRegularExpression('#<form[^>]+action="[^"]*/plain-combined/[^"]*"#', $body);
    }

    #[Test]
    public function searchWithoutListTargetsTheConfiguredCentralSearchPage(): void
    {
        $body = $this->render(30);

        // No list here -> form falls back to the settings central pid (page 40),
        // i.e. NOT the current page 30.
        self::assertMatchesRegularExpression('#<form[^>]+action="[^"]*/central-search/[^"]*"#', $body);
        self::assertDoesNotMatchRegularExpression('#<form[^>]+action="[^"]*/search-only/[^"]*"#', $body);
    }

    #[Test]
    public function locksEachPresetTownAsItsOwnHiddenInput(): void
    {
        $body = $this->render(60);

        self::assertMatchesRegularExpression('#name="tx_thuecat_touristattractionlist\[demand\]\[towns\]\[0\]" value="1"#', $body);
        self::assertMatchesRegularExpression('#name="tx_thuecat_touristattractionlist\[demand\]\[towns\]\[1\]" value="2"#', $body);
        self::assertStringNotContainsString('<select', $body);
    }

    #[Test]
    public function readsTheTranslatedFilteredPresetForTheRequestedLanguage(): void
    {
        // default language locks Erfurt (town 1); the en overlay locks Weimar (2).
        $lockedTown = '#<input type="hidden" name="tx_thuecat_touristattractionlist\[demand\]\[towns\]\[\d*\]" value="%d"#';

        self::assertMatchesRegularExpression(sprintf($lockedTown, 1), $this->render(50));
        self::assertMatchesRegularExpression(sprintf($lockedTown, 2), $this->render(50, 1));
    }

    private function render(int $pageId, int $language = 0): string
    {
        $request = (new InternalRequest())->withPageId($pageId);
        if ($language > 0) {
            $request = $request->withLanguageId($language);
        }

        return (string)$this->executeFrontendSubRequest($request)->getBody();
    }
}
