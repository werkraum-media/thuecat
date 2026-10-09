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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

abstract class AbstractEventsEntity extends AbstractEntity
{
    protected string $source_name = '';
    protected string $source_url = '';

    protected int $thuecat_import_configuration = 0;

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->source_name = 'thuecat';  // intentionally hardcoded, to distinguish from destionation.one direct import via ext:events
        $this->source_url = $parserContext->apiDomain !== '' ? $parserContext->apiDomain : FetchData::DEFAULT_API_DOMAIN;

        $this->thuecat_import_configuration = $parserContext->importConfigurationUid;
    }
}
