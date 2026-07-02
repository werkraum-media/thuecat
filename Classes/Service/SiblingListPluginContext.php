<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Service;

use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\EditorFilter;

/**
 * The question is raised by the search plugin for any list siblings on its PID.
 * A list content element found on the page: its editor preset and storage pages.
 */
class SiblingListPluginContext
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
