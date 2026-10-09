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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * One stretch of a trail covered by a single surface type
 */
class TrailWayTypeEntity extends AbstractEntity
{
    public const TABLE = 'tx_thuecat_trail_way_type';

    public const SEPARATOR = '::wt::';

    protected string $remote_id = '';
    protected string $title = '';
    protected string $length = '';
    protected string $length_unit = '';

    /**
     * @param array<string, mixed> $node a single thuecat:wayTypeLegend segment
     */
    public function configure(array $node, string $language, string $parentRemoteId = '', int $ordinal = 0): void
    {
        // The segment's own @id is a blank node, so position is the only
        // identity that survives a re-import.
        $this->remote_id = $parentRemoteId . self::SEPARATOR . $ordinal;
        $this->title = $this->extractValue($node['thuecat:wayTypeTitle'] ?? null, $language);

        $length = $node['thuecat:wayTypeLength'] ?? null;
        if (!is_array($length)) {
            return;
        }

        $this->length = $this->extractValue($length['schema:value'] ?? null, $language);
        $this->length_unit = $this->stripNamespacePrefix(
            $this->extractValue($length['schema:unitCode'] ?? null, $language)
        );
    }

    public function configureTranslation(array $node, string $language, int $sysLanguageUid): void
    {
        $this->recordTranslation(
            'title',
            $this->extractValue($node['thuecat:wayTypeTitle'] ?? null, $language),
            $sysLanguageUid
        );
    }

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
    }

    public function handlesTypes(): array
    {
        return [];
    }
}
