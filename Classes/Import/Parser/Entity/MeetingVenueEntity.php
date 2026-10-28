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
 * A meeting place stays an attraction, so it writes the attraction table as its own record type.
 * Payloads carry the full class chain, so subclasses of MeetingCongress are claimed through it.
 */
class MeetingVenueEntity extends TouristAttractionEntity
{
    protected int $priority = 30;
    protected string $type = 'meeting_venue';
    protected string $venue_type = '';

    /**
     * @param array<string, mixed> $node
     * @param array<string, int> $translationLanguages
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        parent::parse($node, $language, $parserContext, $translationLanguages);

        $types = is_array($node['@type'] ?? null) ? $node['@type'] : [];
        $this->venue_type = in_array('thuecat:MeetingCongressHotel', $types, true)
            ? 'meeting_congress_hotel'
            : 'meeting_congress';
    }

    public function handlesTypes(): array
    {
        return ['thuecat:MeetingCongressHotel', 'thuecat:MeetingCongress'];
    }
}
