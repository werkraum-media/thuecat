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

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// schema:description reaches the details column from either shape ThueCat
// delivers, preferring HTML and falling back to escaped plain text.
class EventDescriptionImportTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventDescriptionImportPreState.php');
        $this->expectFetch('e_desc_html-hubev.json');
        $this->expectFetch('e_desc_textobject-hubev.json');
        $this->expectFetch('e_desc_textonly-hubev.json');
        $this->expectFetch('e_desc_literal-hubev.json');
        $this->expectFetch('e_desc_none-hubev.json');

        $this->importConfiguration(1);
    }

    #[Test]
    public function thuecatHtmlEntryFillsDetails(): void
    {
        self::assertSame(
            '<p>Am 28. Juni findet ein CSD-Special statt.</p>',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_desc_html-hubev')['details'] ?? null
        );
    }

    /**
     * The fixture carries the same text in en and de; the run is de.
     */
    #[Test]
    public function textObjectHtmlFillsDetailsInTheImportLanguage(): void
    {
        self::assertSame(
            '<p>Eine Testveranstaltung.</p>',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_desc_textobject-hubev')['details'] ?? null
        );
    }

    #[Test]
    public function textObjectWithoutHtmlFallsBackToPlainText(): void
    {
        self::assertSame(
            'Nur Text ohne HTML.',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_desc_textonly-hubev')['details'] ?? null
        );
    }

    /**
     * Also proves the fallback is escaped: < and & must not become markup.
     */
    #[Test]
    public function plainLiteralWithoutHtmlEntryFallsBackToEscapedText(): void
    {
        self::assertSame(
            'Tom &amp; Jerry &lt;live&gt;',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_desc_literal-hubev')['details'] ?? null
        );
    }

    #[Test]
    public function eventWithoutDescriptionIsImportedWithEmptyDetails(): void
    {
        $event = $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_desc_none-hubev');

        self::assertSame('Veranstaltung ohne Beschreibung', $event['title'] ?? null);
        self::assertSame('', $event['details'] ?? '');
    }
}
