<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * The trail show page carries a cache tag for the record it displays, so an
 * import updating or deleting that record discards the rendered page.
 */
class TrailShowPageCacheTest extends AbstractCachingTestCase
{
    private const TABLE = 'tx_thuecat_trail';

    protected function getDataSetFileName(): string
    {
        return 'TrailsForPreselection.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
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
    public function savingTheConfiguredRecordDiscardsThePage(): void
    {
        $this->request(10);
        self::assertCount(1, $this->cacheIdentifiersTaggedWith('pages', 'pageId_10'));

        $this->saveRecord(21, ['title' => 'Goethe-Erlebnisweg, neu benannt'], self::TABLE);

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
        $this->saveRecord(21, ['title' => 'Goethe-Erlebnisweg, neu benannt'], self::TABLE);

        $body = (string)$this->request(10)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg, neu benannt', $body);
        self::assertStringNotContainsString(
            '<h2>Goethe-Erlebnisweg</h2>',
            $body,
            'The superseded title must not survive in a cached page.'
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
}
