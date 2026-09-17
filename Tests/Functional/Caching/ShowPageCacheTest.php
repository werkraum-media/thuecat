<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * The show page carries a cache tag for the record it displays, so an import
 * updating or deleting that record discards the rendered page.
 *
 * On the backend-selection path the tag comes from the configured uid rather
 * than the resolved record: a page rendering the empty state because its pick
 * vanished must still be discarded once that uid exists again.
 */
class ShowPageCacheTest extends AbstractCachingTestCase
{
    private const TABLE = 'tx_thuecat_tourist_attraction';

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPreselection.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    /**
     * Guard for the tests below: if this fails, the harness cannot observe page
     * caching at all and a green result elsewhere proves nothing.
     */
    #[Test]
    public function theHarnessObservesPageCacheEntries(): void
    {
        $cold = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');
        $this->request(10);
        $warm = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(1, $warm, 'A show request must leave one page-cache entry.');
    }

    #[Test]
    public function theConfiguredRecordTagsThePage(): void
    {
        $this->request(10);

        self::assertNotSame(
            [],
            $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_21'),
            'The configured record must tag the page it is displayed on.'
        );
    }

    #[Test]
    public function aRecordResolvedFromTheQueryParameterTagsThePage(): void
    {
        // Via detailRequest: the record argument is cacheable, so a request
        // without a valid cHash never reaches the controller.
        $this->executeFrontendSubRequest(
            $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '23', 11)
        );

        self::assertNotSame(
            [],
            $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_23'),
            'A record shown via the query parameter must tag the page.'
        );
    }

    #[Test]
    public function savingTheConfiguredRecordDiscardsThePage(): void
    {
        $this->request(10);
        self::assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'));

        $this->saveRecord(21, ['title' => 'Stadtmuseum Erfurt, neu benannt'], self::TABLE);

        self::assertSame(
            [],
            $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'),
            'Saving the displayed record must discard its page.'
        );
    }

    #[Test]
    public function theNextRequestRendersTheSavedValue(): void
    {
        $this->request(10);
        $this->saveRecord(21, ['title' => 'Stadtmuseum Erfurt, neu benannt'], self::TABLE);

        $body = (string)$this->request(10)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt, neu benannt', $body);
        self::assertStringNotContainsString(
            '<h2>Stadtmuseum Erfurt</h2>',
            $body,
            'The superseded title must not survive in a cached page.'
        );
    }

    #[Test]
    public function deletingTheConfiguredRecordLeavesNoResidue(): void
    {
        $this->request(13);

        $this->deleteRecord(24, self::TABLE);

        $body = (string)$this->request(13)->getBody();

        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertStringNotContainsString('Ort der verschwindet', $body);
    }

    /**
     * What tagging from the configured uid buys: the empty page holds the tag
     * of a record that does not exist, so recreating it discards the page.
     */
    #[Test]
    public function anEmptyPageIsDiscardedWhenTheConfiguredRecordReturns(): void
    {
        $this->deleteRecord(24, self::TABLE);
        $this->request(13);
        self::assertCount(
            1,
            $this->cacheIdentifiersTaggedWith('pages', 'pageId_13'),
            'The empty page is cached.'
        );

        $this->undeleteRecord(24, self::TABLE);

        self::assertSame(
            [],
            $this->cacheIdentifiersTaggedWith('pages', 'pageId_13'),
            'A restored record must discard the empty page that waited for it.'
        );
    }

    #[Test]
    public function anEmptyPageCarriesTheConfiguredRecordTag(): void
    {
        $this->deleteRecord(24, self::TABLE);

        $this->request(13);

        self::assertNotSame(
            [],
            $this->cacheIdentifiersTaggedWith('pages', self::TABLE . '_24'),
            'The empty page must stay reachable through the configured uid.'
        );
    }

    #[Test]
    public function repeatingARequestReusesItsEntry(): void
    {
        $this->request(10);
        $afterFirst = $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');

        $this->request(10);

        self::assertSame(
            $afterFirst,
            $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'),
            'The same request must not create a second entry.'
        );
    }
}
