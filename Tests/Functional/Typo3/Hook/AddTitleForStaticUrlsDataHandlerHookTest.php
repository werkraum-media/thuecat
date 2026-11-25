<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
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

namespace WerkraumMedia\ThueCat\Tests\Functional\Typo3\Hook;

use Codappix\Typo3PhpDatasets\PhpDataSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Typo3\Hook\AddTitleForStaticUrlsDataHandlerHook;

#[CoversClass(AddTitleForStaticUrlsDataHandlerHook::class)]
final class AddTitleForStaticUrlsDataHandlerHookTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/../../Fixtures/AddTitleForStaticUrlsDataHandlerHook/Guzzle/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/BasicPages.php');
    }

    /**
     * @param array<int, array<string, string>> $submittedUrls keyed by URL record uid
     */
    #[Test]
    #[DataProvider('possibleUpdatesForTitle')]
    public function changesTitleFromRemote(string $existingTitle, array $submittedUrls): void
    {
        new PhpDataSet()->import([
            'tx_thuecat_import_configuration' => [
                [
                    'uid' => 1,
                    'pid' => 10,
                    'title' => 'Existing Configuration',
                    'type' => 'static',
                    'urls' => 2,
                ],
            ],
            'tx_thuecat_import_configuration_url' => [
                ['uid' => 1, 'pid' => 10, 'configuration' => 1, 'sorting' => 1, 'title' => $existingTitle, 'url' => 'https://example.com/one'],
                ['uid' => 2, 'pid' => 10, 'configuration' => 1, 'sorting' => 2, 'title' => $existingTitle, 'url' => 'https://example.com/two'],
            ],
        ]);

        $this->expectFetch('r_22033250-oapoi.json');
        $this->expectFetch('809459960188-epwb.json');

        $this->processDataMap([
            'tx_thuecat_import_configuration' => [
                1 => ['title' => 'Test', 'type' => 'static', 'urls' => '1,2'],
            ],
            'tx_thuecat_import_configuration_url' => $submittedUrls,
        ]);

        $this->assertCSVDataSet(__DIR__ . '/Assertions/UrlTitlesFromRemote.csv');
    }

    /**
     * @return iterable<string, array{existingTitle: string, submittedUrls: array<int, array<string, string>>}>
     */
    public static function possibleUpdatesForTitle(): iterable
    {
        yield 'when no titles are provided within TYPO3' => [
            'existingTitle' => '',
            'submittedUrls' => [
                1 => ['url' => 'https://thuecat.org/resources/r_22033250-oapoi'],
                2 => ['url' => 'https://thuecat.org/resources/809459960188-epwb'],
            ],
        ];

        yield 'when titles already existed' => [
            'existingTitle' => 'Existing Titel',
            'submittedUrls' => [
                1 => ['url' => 'https://thuecat.org/resources/r_22033250-oapoi'],
                2 => ['url' => 'https://thuecat.org/resources/809459960188-epwb'],
            ],
        ];

        yield 'when titles were submitted as well' => [
            'existingTitle' => 'Existing Titel',
            'submittedUrls' => [
                1 => ['title' => 'New Titel Eins', 'url' => 'https://thuecat.org/resources/r_22033250-oapoi'],
                2 => ['title' => 'New Titel Zwei', 'url' => 'https://thuecat.org/resources/809459960188-epwb'],
            ],
        ];
    }

    #[Test]
    public function addsTitleForAllUrlsInNewRecord(): void
    {
        $this->expectFetch('r_22033250-oapoi.json');
        $this->expectFetch('809459960188-epwb.json');

        $this->processDataMap([
            'tx_thuecat_import_configuration' => [
                'NEW67a1e37c5cc8c460046430' => [
                    'title' => 'test new',
                    'type' => 'static',
                    'pid' => '10',
                    'urls' => 'NEW67a1b62355ad6737507957,NEW67a1bb94b4f41262966709',
                ],
            ],
            'tx_thuecat_import_configuration_url' => [
                'NEW67a1b62355ad6737507957' => [
                    'pid' => '10',
                    'url' => 'https://thuecat.org/resources/r_22033250-oapoi',
                ],
                'NEW67a1bb94b4f41262966709' => [
                    'pid' => '10',
                    'url' => 'https://thuecat.org/resources/809459960188-epwb',
                ],
            ],
        ]);

        $this->assertCSVDataSet(__DIR__ . '/Assertions/UrlTitlesFromRemote.csv');
    }

    #[Test]
    public function doesNothingIfNewRecordDoesNotContainUrls(): void
    {
        $this->processDataMap([
            'tx_thuecat_import_configuration' => [
                'NEW67a1e37c5cc8c460046430' => [
                    'title' => 'test new',
                    'type' => 'static',
                    'pid' => '10',
                ],
            ],
        ]);

        $this->assertSame(0, $this->countRows('tx_thuecat_import_configuration_url'));
    }

    #[Test]
    public function keepsTheTitleOfAResourceOfAKindNotImported(): void
    {
        new PhpDataSet()->import([
            'tx_thuecat_import_configuration_url' => [
                ['uid' => 1, 'pid' => 10, 'configuration' => 1, 'title' => 'Own title', 'url' => 'https://thuecat.org/resources/r_22033250-oapoi'],
            ],
        ]);
        $this->expectFetch('test-unknown-kind.json');

        $this->processDataMap([
            'tx_thuecat_import_configuration_url' => [
                1 => ['url' => 'https://thuecat.org/resources/test-unknown-kind'],
            ],
        ]);

        $this->assertSame(['Own title'], $this->fetchColumnMatching('tx_thuecat_import_configuration_url', 'title', ['uid' => 1]));
    }
}
