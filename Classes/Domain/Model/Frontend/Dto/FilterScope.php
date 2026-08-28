<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

/**
 * What every filter field is built against, resolved once per request.
 *
 * Carries the record kind the offered values must be reached through, so a
 * field declares its own value shape and the scope decides which records it is
 * asked about.
 *
 * Anchors resolve per site and therefore live here rather than on a field:
 * a declaration states which setting to resolve, and one build holds what it
 * resolved to.
 */
final class FilterScope
{
    /**
     * @param int[] $storagePageIds Empty means unrestricted storage.
     * @param array<string, int> $anchors Resolved uid per anchor setting name.
     * @param int[] $sitePageIds Empty means unrestricted by site.
     */
    public function __construct(
        private readonly string $recordTable,
        private readonly array $storagePageIds,
        private readonly ?EditorFilter $editorFilter,
        private readonly array $anchors = [],
        private readonly array $sitePageIds = [],
    ) {
    }

    /** No sibling list: each field offers what its storage holds. */
    public static function unscoped(string $recordTable): self
    {
        return new self($recordTable, [], null);
    }

    /**
     * The pages of the site the request runs in.
     *
     * A value outside them is dropped even when it is related to a delivered
     * record and sits in the anchor's rootline. Neither of those bounds the
     * site: a record may relate across sites, and a parent pointing into our
     * tree puts a foreign value in our rootline.
     *
     * @return int[]
     */
    public function getSitePageIds(): array
    {
        return $this->sitePageIds;
    }

    /** 0 when unconfigured, which switches the field's options off. */
    public function anchorFor(CategoryAnchorSetting $setting): int
    {
        return $this->anchors[$setting->name] ?? 0;
    }

    public function getRecordTable(): string
    {
        return $this->recordTable;
    }

    /**
     * @return int[]
     */
    public function getStoragePageIds(): array
    {
        return $this->storagePageIds;
    }

    public function getEditorFilter(): ?EditorFilter
    {
        return $this->editorFilter;
    }
}
