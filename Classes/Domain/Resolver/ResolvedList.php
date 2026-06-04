<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Resolver;

use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\EditorFilter;

/**
 * A list content element found on the page: its editor preset and storage pages.
 */
class ResolvedList
{
    /**
     * @param int[] $storagePageIds
     */
    public function __construct(
        protected readonly EditorFilter $editorFilter,
        protected readonly array $storagePageIds,
    ) {
    }

    public function getEditorFilter(): EditorFilter
    {
        return $this->editorFilter;
    }

    /**
     * @return int[]
     */
    public function getStoragePageIds(): array
    {
        return $this->storagePageIds;
    }
}
