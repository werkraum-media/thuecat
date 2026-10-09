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

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

class TrailMetaTagsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForShow.php';
    }

    #[Test]
    public function emitsKeywordsMetaTagFromRelatedCategories(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="Themenweg, Fahrradfreundlich"#',
            $body
        );
    }

    #[Test]
    public function emitsNoKeywordsMetaTagWithoutRelations(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '20');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }

    #[Test]
    public function keywordsMetaTagUsesTranslatedTitles(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21', 10, 1);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="themed trail, bicycle friendly"#',
            $body
        );
    }
}
