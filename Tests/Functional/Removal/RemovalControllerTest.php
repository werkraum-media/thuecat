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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use WerkraumMedia\ThueCat\Controller\Backend\RemovalController;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\RemovalJobs;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class RemovalControllerTest extends AbstractRemovalTestCase
{
    private const int NOW = 1795086000;

    /**
     * Within one table row.
     */
    private const string ROW = '(?:(?!</tr>).)*';

    private const string WORKER_DOCUMENTATION
        = 'https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Integration/RemovalModule.html#removal-module-worker';

    private const string CLEANUP_DOCUMENTATION
        = 'https://docs.typo3.org/p/werkraummedia/thuecat/main/en-us/Integration/Maintenance.html#maintenance-cleanup-tasks';

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
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(RemovalJobs::class, new RemovalJobs(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@' . self::NOW)),
        ));
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
        $this->assertSame(8, substr_count($html, 'selection%5Bscope%5D=site'), 'Two sites with four kinds each');
    }

    #[Test]
    public function indexSaysSoWhenNoPageHoldsRoots(): void
    {
        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('No page holds POIs, places, trails or events.', $html);
    }

    #[Test]
    public function indexListsFinishedJobsWithTheirResultsBeforeTheOtherJobsWithTheirStatus(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Jobs.csv');

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#id="removal-jobs-finished".*<tr data-job="2">.*<tr data-job="1">.*'
            . 'id="removal-jobs-open".*<tr data-job="4">.*<tr data-job="3">#s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<tr data-job="1">' . self::ROW . 'Tourist Attraction of folder Folder A \[10\]' . self::ROW . 'admin'
            . self::ROW . '>12<' . self::ROW . '>0<' . self::ROW . '>3<' . self::ROW . '>20<' . self::ROW . '>0<#s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<tr data-job="2">' . self::ROW . '\[32\] in site Example site' . self::ROW . 'admin'
            . self::ROW . '>4<' . self::ROW . '>1<' . self::ROW . '>0<' . self::ROW . '>0<' . self::ROW . '>1<'
            . self::ROW . 'tx_thuecat_place:1 Attempt to delete record without delete-permissions#s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<tr data-job="4">' . self::ROW . 'Event of site Example site' . self::ROW . 'running' . self::ROW . 'admin'
            . self::ROW . '>7<#s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<tr data-job="3">' . self::ROW . 'Trail of the whole instance' . self::ROW . 'queued' . self::ROW . 'admin#s',
            $html
        );
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function stalledJobs(): iterable
    {
        yield 'queued longer than five minutes' => ['queued', self::NOW - 301, true];
        yield 'queued for five minutes' => ['queued', self::NOW - 300, false];
        yield 'running without progress for longer than five minutes' => ['running', self::NOW - 301, true];
        yield 'running with progress within five minutes' => ['running', self::NOW - 300, false];
        yield 'done long ago' => ['done', self::NOW - 3600, false];
    }

    #[DataProvider('stalledJobs')]
    #[Test]
    public function indexMarksAJobNobodyTakesFurtherAsStalled(string $status, int $progressedAt, bool $stalled): void
    {
        $this->insertJob($status, self::NOW - 3600, $progressedAt);

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertSame($stalled, (bool)preg_match('#<tr data-job="1">' . self::ROW . '>stalled<#s', $html));
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function stalledJobCounts(): iterable
    {
        yield 'three stalled jobs' => [3, true];
        yield 'two stalled jobs' => [2, false];
    }

    #[DataProvider('stalledJobCounts')]
    #[Test]
    public function indexPointsAtTheWorkerTaskWhenMoreThanTwoJobsStall(int $stalledJobs, bool $notice): void
    {
        for ($job = 0; $job < $stalledJobs; $job++) {
            $this->insertJob('queued', self::NOW - 3600, self::NOW - 3600);
        }

        $this->insertJob('running', self::NOW - 3600, self::NOW);

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertSame($notice, str_contains($html, self::WORKER_DOCUMENTATION));
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function oldestJobs(): iterable
    {
        yield 'a finished job older than ten days' => ['done', self::NOW - 864001, true];
        yield 'a finished job ten days old' => ['done', self::NOW - 864000, false];
        yield 'a queued job older than ten days' => ['queued', self::NOW - 864001, false];
    }

    #[DataProvider('oldestJobs')]
    #[Test]
    public function indexPointsAtTheCleanupTasksWhenTheOldestFinishedJobIsOlderThanTenDays(
        string $status,
        int $createdAt,
        bool $notice,
    ): void {
        $this->insertJob($status, $createdAt, $createdAt);
        $this->insertJob('done', self::NOW - 3600, self::NOW - 3600);

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertSame($notice, str_contains($html, self::CLEANUP_DOCUMENTATION));
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
        $flashMessages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        $this->assertSame('Removal queued', $flashMessages[0]->getTitle());
        $jobs = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_removal_job')
            ->select(['*'], 'tx_thuecat_removal_job')
            ->fetchAllAssociative()
        ;
        $this->assertCount(1, $jobs);
        $this->assertEquals($expectedJob, array_intersect_key($jobs[0], $expectedJob));
        $jobUid = $jobs[0]['uid'];
        $this->assertIsNumeric($jobUid);
        $this->assertEquals([new RemovalStarted((int)$jobUid)], $this->queuedMessages());
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
        $this->assertSame([], $this->queuedMessages());
    }
}
