<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\CategoryNode;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;

// The search form's category tree: from the categories attractions in storage
// actually use, walk up to their roots and offer those full subtrees. Roots no
// attraction touches must not leak in.
class TouristAttractionCategoryOptionsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilter.php';
    }

    #[Test]
    public function buildsCategoryTreeOfUsedRootsSortedByTitlePerLevel(): void
    {
        $repository = $this->get(TouristAttractionRepository::class);

        $tree = $repository->findCategoryTreeForSearchForm([11]);

        // Museum/Kirche hang below root Gebäudetyp, Haus is a root itself. Root
        // Region is untouched, so neither it nor Innenstadt appear.
        self::assertSame(
            [
                'Gebäudetyp' => [
                    'Burg' => [],
                    'Kirche' => [],
                    'Museum' => [
                        'Freilichtmuseum' => [],
                    ],
                ],
                'Haus' => [],
            ],
            $this->flatten($tree)
        );
    }

    #[Test]
    public function everyTreeNodeCarriesItsCategoryForTheCheckbox(): void
    {
        $repository = $this->get(TouristAttractionRepository::class);

        $tree = $repository->findCategoryTreeForSearchForm([11]);

        $root = $tree[0];
        self::assertSame('Gebäudetyp', $root->getCategory()->getTitle());
        self::assertSame(100, $root->getCategory()->getUid());
    }

    /**
     * @param CategoryNode[] $nodes
     *
     * @return array<string, mixed>
     */
    private function flatten(array $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $result[$node->getCategory()->getTitle()] = $this->flatten($node->getChildren());
        }

        return $result;
    }
}
