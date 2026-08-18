<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;

// The search form's keyword tree. Keywords hang below their own anchor, which is
// a container rather than an offered term, so the tree starts at the sets below
// it. The type-category tree has its own anchor and must never appear here.
class TouristAttractionKeywordsOptionsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilter.php';
    }

    #[Test]
    public function buildsKeywordTreeOfUsedSetsSortedByTitlePerLevel(): void
    {
        $repository = $this->get(TouristAttractionRepository::class);

        $tree = $repository->findKeywordsTreeForSearchForm([11], 500);

        self::assertSame(
            [
                'Ambiente' => [
                    'modern' => [],
                    'romantisch' => [],
                ],
                'Hauptstadt' => [
                    'Erfurt' => [],
                ],
            ],
            $this->flatten($tree)
        );
    }

    // 2.4: a configured tree offers its own descendants and nothing else. The
    // fixture's categories and keywords sit in one storage, so a tree built
    // without regard to the anchor would pull Gebäudetyp/Haus in beside them.
    #[Test]
    public function offersOnlyDescendantsOfTheKeywordAnchor(): void
    {
        $repository = $this->get(TouristAttractionRepository::class);

        $tree = $repository->findKeywordsTreeForSearchForm([11], 500);

        self::assertSame(
            ['Ambiente', 'Hauptstadt'],
            array_keys($this->flatten($tree)),
            'Only the keyword anchor\'s own sets are offered.'
        );
    }
}
