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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\ApplicationContext;
use WerkraumMedia\ThueCat\Import\Http\ClientBudget;
use WerkraumMedia\ThueCat\Import\Http\DeniedRequestLoggingClient;
use WerkraumMedia\ThueCat\Import\Http\ImportClientFactory;
use WerkraumMedia\ThueCat\Import\Http\RequestLedger;
use WerkraumMedia\ThueCat\Import\Http\RetryTally;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;

final class ImportClientFactoryTest extends TestCase
{
    #[Test]
    public function logsDeniedRequestsInDevelopment(): void
    {
        self::assertInstanceOf(
            DeniedRequestLoggingClient::class,
            $this->factory()->build(new ClientBudget(), new ApplicationContext('Development'))
        );
    }

    #[Test]
    public function logsNoDeniedRequestsInProduction(): void
    {
        self::assertNotInstanceOf(
            DeniedRequestLoggingClient::class,
            $this->factory()->build(new ClientBudget(), new ApplicationContext('Production'))
        );
    }

    protected function factory(): ImportClientFactory
    {
        return new ImportClientFactory(
            self::createStub(ImportSettings::class),
            new RetryTally(),
            new CapturingLogger(),
            new RequestLedger()
        );
    }
}
