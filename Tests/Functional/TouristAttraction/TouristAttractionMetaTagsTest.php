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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionMetaTagsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForShow.php';
    }

    #[Test]
    public function emitsKeywordsMetaTagFromRelatedCategories(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->showRequest('21'))->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="romantisch, barrierefrei"#',
            $body
        );
    }

    #[Test]
    public function emitsNoKeywordsMetaTagWithoutRelations(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->showRequest('20'))->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }

    #[Test]
    public function keywordsMetaTagUsesTranslatedTitles(): void
    {
        $request = $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '21', 10, 1);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<meta[^>]+name="keywords"[^>]+content="romantic, accessible"#',
            $body
        );
    }

    private function showRequest(string $attractionUid): InternalRequest
    {
        return $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', $attractionUid);
    }
}
