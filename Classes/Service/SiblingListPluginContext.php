<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

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
