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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use WerkraumMedia\ThueCat\Controller\Backend\RemovalController;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

final class RemovalControllerTest extends AbstractBackendTestCase
{
    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $this->request = new ServerRequest('https://example.com/typo3/module/thuecat/removal')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withAttribute('route', $this->get(Router::class)->getRoute('thuecat_removal'))
            ->withAttribute('module', $this->get(ModuleProvider::class)->getModule('thuecat_removal', $backendUser))
        ;
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    #[Test]
    public function indexListsThePagesHoldingRootsPerSiteAndTheOriginsOfEachSite(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('Example site', $html);
        $this->assertStringContainsString('Folder A', $html);
        $this->assertStringContainsString('Folder B', $html);
        $this->assertStringContainsString('First site', $html);
        $this->assertStringContainsString('Folder C', $html);
        $this->assertStringContainsString('Pages outside any site', $html);
        $this->assertStringContainsString('Folder D', $html);
        $this->assertStringContainsString('Erfurt', $html);
        $this->assertStringContainsString('Weimar', $html);
        $this->assertStringNotContainsString('Folder without roots', $html);
    }

    #[Test]
    public function indexSaysSoWhenNoPageHoldsRoots(): void
    {
        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('No page holds POIs, places, trails or events.', $html);
    }

    /**
     * @return iterable<string, array{array<string, string|int>, string}>
     */
    public static function confirmations(): iterable
    {
        yield 'attractions of a folder' => [
            ['table' => 'tx_thuecat_tourist_attraction', 'scope' => 'folder', 'page' => 10],
            '2 roots',
        ];
        yield 'events of a site' => [
            ['table' => 'tx_events_domain_model_event', 'scope' => 'site', 'page' => 1],
            '2 roots',
        ];
        yield 'trails of the instance' => [
            ['table' => 'tx_thuecat_trail', 'scope' => 'instance', 'page' => 0],
            '2 roots',
        ];
        yield 'an import within a site' => [
            ['origin' => 32, 'page' => 1],
            '3 roots',
        ];
    }

    /**
     * @param array<string, string|int> $selection
     */
    #[DataProvider('confirmations')]
    #[Test]
    public function confirmShowsHowManyRootsTheSelectionCoversAndQueuesNothing(array $selection, string $roots): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');

        $html = (string)$this->get(RemovalController::class)
            ->confirmAction($this->request->withQueryParams(['selection' => $selection]))
            ->getBody()
        ;

        $this->assertStringContainsString($roots, $html);
        $this->assertSame(0, $this->countRows('tx_thuecat_removal_job'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, int|string>}>
     */
    public static function starts(): iterable
    {
        yield 'attractions of a folder, with files and index update' => [
            [
                'selection' => ['table' => 'tx_thuecat_tourist_attraction', 'scope' => 'folder', 'page' => 10],
                'removeFiles' => '1',
                'updateReferenceIndex' => '1',
            ],
            [
                'status' => 'queued',
                'backend_user' => 1,
                'root_table' => 'tx_thuecat_tourist_attraction',
                'scope' => 'folder',
                'scope_page' => 10,
                'origin_configuration_uid' => 0,
                'remove_files' => 1,
                'update_reference_index' => 1,
            ],
        ];
        yield 'an import within a site, without files and index update' => [
            [
                'selection' => ['origin' => 32, 'page' => 1],
            ],
            [
                'status' => 'queued',
                'backend_user' => 1,
                'root_table' => '',
                'scope' => 'site',
                'scope_page' => 1,
                'origin_configuration_uid' => 32,
                'remove_files' => 0,
                'update_reference_index' => 0,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, int|string> $expectedJob
     */
    #[DataProvider('starts')]
    #[Test]
    public function startQueuesTheJobWithoutRemovingAnything(array $body, array $expectedJob): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');

        $response = $this->get(RemovalController::class)->startAction(
            $this->request->withMethod('POST')->withParsedBody($body)
        );

        $this->assertSame(303, $response->getStatusCode());
        $jobs = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_removal_job')
            ->select(['*'], 'tx_thuecat_removal_job')
            ->fetchAllAssociative()
        ;
        $this->assertCount(1, $jobs);
        $this->assertEquals($expectedJob, array_intersect_key($jobs[0], $expectedJob));
        $messages = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')
            ->select(['body'], 'sys_messenger_messages', ['queue_name' => 'default'])
            ->fetchAllAssociative()
        ;
        $this->assertCount(1, $messages);
        $body = $messages[0]['body'];
        $this->assertIsString($body);
        $message = new PhpSerializer()->decode(['body' => $body])->getMessage();
        $this->assertInstanceOf(RemovalStarted::class, $message);
        $this->assertEquals($jobs[0]['uid'], $message->jobUid);
        $this->assertSame(5, $this->countRows('tx_thuecat_tourist_attraction'));
    }

    /**
     * @return iterable<string, array{array<string, string|int>}>
     */
    public static function invalidSelections(): iterable
    {
        yield 'a table that holds no roots' => [['table' => 'pages', 'scope' => 'folder', 'page' => 10]];
        yield 'an unknown scope' => [['table' => 'tx_thuecat_trail', 'scope' => 'galaxy', 'page' => 10]];
        yield 'a folder without page' => [['table' => 'tx_thuecat_trail', 'scope' => 'folder']];
        yield 'an import without site' => [['origin' => 32]];
    }

    /**
     * @param array<string, string|int> $selection
     */
    #[DataProvider('invalidSelections')]
    #[Test]
    public function anInvalidSelectionIsNeitherConfirmedNorQueued(array $selection): void
    {
        $confirm = $this->get(RemovalController::class)
            ->confirmAction($this->request->withQueryParams(['selection' => $selection]))
        ;
        $start = $this->get(RemovalController::class)->startAction(
            $this->request->withMethod('POST')->withParsedBody(['selection' => $selection])
        );

        $this->assertSame(303, $confirm->getStatusCode());
        $this->assertSame(303, $start->getStatusCode());
        $this->assertSame(0, $this->countRows('tx_thuecat_removal_job'));
        $this->assertSame(
            0,
            $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')->count('*', 'sys_messenger_messages', [])
        );
    }
}
