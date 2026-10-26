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

class EventTicketImportTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventTicketImportPreState.php');
        $this->expectFetch('e_ticket_single-hubev.json');
        $this->expectFetch('e_ticket_list-hubev.json');
        $this->expectFetch('e_ticket_nourl-hubev.json');

        $this->importConfiguration(1);
    }

    #[Test]
    public function singleOfferNodeFillsTicket(): void
    {
        self::assertSame(
            'https://www.eventim.de/?affiliate=EUM',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_ticket_single-hubev')['ticket'] ?? null
        );
    }

    /**
     * The price offer without a url comes first, so reading the list as one
     * node, or taking its first entry, loses the link.
     */
    #[Test]
    public function listOfOffersFillsTicketFromTheOfferCarryingAUrl(): void
    {
        self::assertSame(
            'https://www.eventim.de/?affiliate=EUM',
            $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_ticket_list-hubev')['ticket'] ?? null
        );
    }

    #[Test]
    public function listOfOffersWithoutAnyUrlLeavesTicketEmpty(): void
    {
        $event = $this->fetchRowByRemoteId('tx_events_domain_model_event', 'https://int.thuecat.org/resources/e_ticket_nourl-hubev');

        self::assertSame('Angebote ohne Link', $event['title'] ?? null);
        self::assertSame('', $event['ticket'] ?? '');
    }
}
