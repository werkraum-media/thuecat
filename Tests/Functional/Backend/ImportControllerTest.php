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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

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
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationOverview;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class ImportControllerTest extends AbstractBackendTestCase
{
    /**
     * Within one table row.
     */
    private const string ROW = '(?:(?!</tr>).)*';

    /**
     * Queued job 3 and running job 1 last progressed then.
     */
    private const int LAST_PROGRESS = 1795572000;

    protected array $configurationToUseInTestInstance = [
        'SYS' => ['phpTimeZone' => 'Europe/Berlin'],
    ];

    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModule.csv');

        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $this->request = new ServerRequest('https://example.com/typo3/module/thuecat/imports')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withAttribute('route', $this->get(Router::class)->getRoute('thuecat_imports'))
            ->withAttribute('module', $this->get(ModuleProvider::class)->getModule('thuecat_imports', $backendUser))
        ;
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    #[Test]
    public function indexGroupsTheConfigurationsBySiteOfTheirStoragePageWithTheOutsideGroupLast(): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#<section data-site="example".*Example site'
            . '.*<tr data-configuration="32">.*<tr data-configuration="60">.*<tr data-configuration="41">'
            . '.*<section data-site="first_site".*First site.*<tr data-configuration="7">'
            . '.*<section data-site="".*Configurations outside any site.*<tr data-configuration="50">#s',
            $html
        );
        $this->assertStringNotContainsString('Deleted configuration', $html);
    }

    /**
     * @return iterable<string, array{int, list<string>}>
     */
    public static function configurationRows(): iterable
    {
        yield 'scheduled daily with a running job' => [
            32,
            ['Erfurt', 'Sync API channel', 'Folder A [10]', 'Daily', '26.11.2026 03:00', '25.11.2026 03:00', 'running'],
        ];
        yield 'manual only, run before' => [
            41,
            ['Weimar', 'Static list of URLs', 'Folder A [10]', 'Manual only', '24.11.2026 22:15'],
        ];
        yield 'an interval of no preset with a queued job' => [
            7,
            ['Gotha', 'Contains Place', 'Folder C [4010]', 'Every 120 minutes', 'queued'],
        ];
        yield 'scheduled weekly, never run' => [
            50,
            ['Jena', 'Synchronization area', 'Folder D [9010]', 'Weekly'],
        ];
    }

    /**
     * @param list<string> $cells
     */
    #[DataProvider('configurationRows')]
    #[Test]
    public function indexShowsEachConfigurationsFactsScheduleRunsAndOpenJob(int $configuration, array $cells): void
    {
        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="' . $configuration . '">' . self::ROW
            . implode(self::ROW, array_map(static fn (string $cell): string => preg_quote($cell, '#'), $cells)) . '#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexShowsNoJobStateForAConfigurationWhoseJobsAreFinished(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '#<tr data-configuration="41">' . self::ROW . 'badge-info#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function lastOutcomes(): iterable
    {
        yield 'done without findings' => [84, 'success', 'Done'];
        yield 'done with warnings' => [41, 'warning', 'Done with warnings'];
        yield 'done with errors' => [82, 'danger', 'Done with errors'];
        yield 'failed after an earlier success' => [81, 'danger', 'Failed'];
        yield 'stopped' => [85, 'secondary', 'Stopped'];
    }

    #[DataProvider('lastOutcomes')]
    #[Test]
    public function indexShowsTheOutcomeOfTheLastFinishedJobBesideTheLastRun(int $configuration, string $state, string $label): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleOutcomes.csv');

        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="' . $configuration . '">' . self::ROW
            . '<td>\s*\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}\s*<span class="badge badge-' . $state . '">' . preg_quote($label, '#') . '</span>\s*</td>#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function configurationsWithoutOutcome(): iterable
    {
        yield 'only a running job' => [32];
        yield 'never run' => [50];
        yield 'running after a failure' => [86];
    }

    #[DataProvider('configurationsWithoutOutcome')]
    #[Test]
    public function indexShowsNoOutcomeWhileAJobIsOpenOrBeforeTheFirstHasFinished(int $configuration): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleOutcomes.csv');

        $this->assertDoesNotMatchRegularExpression(
            '#<tr data-configuration="' . $configuration . '">' . self::ROW . 'badge-(?:success|warning|danger|secondary)#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function jobFoldsByOutcome(): iterable
    {
        yield 'failed' => [81, true];
        yield 'done with errors' => [82, true];
        yield 'done with warnings' => [41, false];
        yield 'done without findings' => [84, false];
        yield 'running after a failure' => [86, false];
    }

    #[DataProvider('jobFoldsByOutcome')]
    #[Test]
    public function indexOpensTheJobsOfAConfigurationWhoseLastJobEndedWithErrors(int $configuration, bool $open): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleOutcomes.csv');

        $this->assertMatchesRegularExpression(
            '#<tr data-jobs="' . $configuration . '">\s*<td[^>]*>\s*<details data-fold="jobs-' . $configuration . '" data-fold-default="[a-z]+"' . ($open ? ' open' : '>') . '#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexKeepsTheJobsTheUserClosedClosedAfterAFailure(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleOutcomes.csv');
        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $backendUser->uc['thuecat_imports'] = ['closed' => 'jobs-81'];

        $this->assertMatchesRegularExpression(
            '#<details data-fold="jobs-81" data-fold-default="open">#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function foldDefaults(): iterable
    {
        yield 'jobs after a failure' => ['jobs-81', 'open'];
        yield 'jobs after warnings' => ['jobs-41', 'closed'];
        yield 'a log' => ['log-2', 'closed'];
    }

    #[DataProvider('foldDefaults')]
    #[Test]
    public function indexTellsTheScriptWhetherAFoldIsOpenWithoutAChoiceOfTheUser(string $fold, string $default): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleOutcomes.csv');

        $this->assertStringContainsString(
            '<details data-fold="' . $fold . '" data-fold-default="' . $default . '"',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexHandsTheScriptTheFoldsTheUserOpenedAndClosed(): void
    {
        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $backendUser->uc['thuecat_imports'] = ['open' => 'jobs-41,log-2', 'closed' => 'jobs-81'];

        $this->assertMatchesRegularExpression(
            '#<div\s+data-imports-status="[^"]+"\s+data-folds-open="jobs-41,log-2"\s+data-folds-closed="jobs-81"#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexRendersEachConfigurationWithItsJobsAsABlockOfItsOwn(): void
    {
        $this->assertMatchesRegularExpression(
            '#<tbody data-configuration-block="41">\s*<tr data-configuration="41">.*<tr data-jobs="41">#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexOffersAButtonToExpandOrCollapseAllJobsOfASiteBesideItsHeadline(): void
    {
        $this->assertMatchesRegularExpression(
            '#<section data-site="example">\s*<h2>\s*Example site\s*<button type="button" class="btn btn-sm btn-default" data-toggle-site-jobs>\s*'
            . '(?:<[^>]+>\s*)*' . 'Expand or collapse all jobs#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexListsOnlyConfigurationsWhoseStoragePageTheUserMayRead(): void
    {
        $this->setUpBackendUser(2);

        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('<tr data-configuration="32">', $html);
        $this->assertStringContainsString('<tr data-configuration="41">', $html);
        $this->assertStringNotContainsString('<tr data-configuration="60">', $html);
        $this->assertStringNotContainsString('<tr data-configuration="7">', $html);
        $this->assertStringNotContainsString('<tr data-configuration="50">', $html);
    }

    #[Test]
    public function indexListsTheJobsOfEachConfigurationNewestFirstBeneathIt(): void
    {
        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="41">.*<tr data-job="2".*<tr data-job="4".*</table>#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexFoldsTheJobsOfEachConfigurationBeneathASummaryNamingTheirNumber(): void
    {
        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="41">.*<tr data-jobs="41">\s*<td[^>]*>\s*<details[^>]*>\s*<summary>Recent jobs \(2\)</summary>.*<tr data-job="2"#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function foldStates(): iterable
    {
        yield 'jobs the user left open' => ['<tr data-jobs="41">', true];
        yield 'a log the user left open' => ['<tr data-job-log="2">', true];
        yield 'jobs the user left closed' => ['<tr data-jobs="7">', false];
    }

    #[DataProvider('foldStates')]
    #[Test]
    public function indexRendersTheBlocksTheUserLeftOpenOpen(string $row, bool $open): void
    {
        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $backendUser->uc['thuecat_imports'] = ['open' => 'jobs-41,log-2'];

        $this->assertMatchesRegularExpression(
            '#' . preg_quote($row, '#') . '\s*<td[^>]*>\s*<details data-fold="[a-z]+-[0-9]+" data-fold-default="[a-z]+"' . ($open ? ' open' : '>') . '#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{int, list<string>}>
     */
    public static function jobRows(): iterable
    {
        yield 'started by an editor, done with skipped items' => [
            2,
            ['done', 'Manual', 'admin', '24.11.2026 22:15', '24.11.2026 22:25', '>12<', '>0<', '>2<'],
        ];
        yield 'scheduled, failed' => [
            4,
            ['failed', 'Scheduled', '23.11.2026 22:15', '23.11.2026 22:16', '>3<', '>1<', '>0<'],
        ];
    }

    /**
     * @param list<string> $cells
     */
    #[DataProvider('jobRows')]
    #[Test]
    public function indexShowsEachJobsStatusTriggerUserTimesAndCounts(int $job, array $cells): void
    {
        $this->assertMatchesRegularExpression(
            '#<tr data-job="' . $job . '"' . self::ROW
            . implode(self::ROW, array_map(static fn (string $cell): string => preg_quote($cell, '#'), $cells)) . '#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function logLines(): iterable
    {
        yield 'first skipped reference' => ['https://thuecat.org/resources/ref-1 Warning: Reference skipped: HTTP 404'];
        yield 'second skipped reference' => ['https://thuecat.org/resources/ref-2 Warning: Reference skipped: HTTP 410'];
        yield 'event–place match' => ['https://thuecat.org/resources/event-1: Related to place 3'];
    }

    #[DataProvider('logLines')]
    #[Test]
    public function indexShowsEachJobsLogEntriesInACollapsibleBlockBeneathIt(string $line): void
    {
        $this->assertMatchesRegularExpression(
            '#<tr data-job="2".*<tr data-job-log="2"' . self::ROW . '<details' . self::ROW . preg_quote($line, '#') . '#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexShowsNoLogBlockForAJobWithoutLog(): void
    {
        $this->assertStringNotContainsString('data-job-log="4"', (string)$this->get(ImportController::class)->indexAction($this->request)->getBody());
    }

    #[Test]
    public function indexListsTheFiveMostRecentJobsOfAConfiguration(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job');
        for ($uid = 101; $uid <= 106; $uid++) {
            $connection->insert('tx_thuecat_import_job', [
                'uid' => $uid,
                'configuration' => 50,
                'status' => 'done',
                'job_trigger' => 'schedule',
                'crdate' => 1795000000 + $uid,
            ]);
        }

        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        foreach ([106, 105, 104, 103, 102] as $uid) {
            $this->assertStringContainsString('<tr data-job="' . $uid . '"', $html);
        }

        $this->assertStringNotContainsString('<tr data-job="101"', $html);
    }

    #[Test]
    public function indexOffersAStartOnlyToAConfigurationWithoutOpenJob(): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="41">' . self::ROW . 'name="configuration" value="41"#s',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '#<tr data-configuration="32">' . self::ROW . 'name="configuration"#s',
            $html
        );
    }

    #[Test]
    public function indexKeepsTheStartOptionsForAModalOpenedFromTheRow(): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="41">' . self::ROW . '<button type="button"[^>]*data-start-import' . self::ROW
            . '<template data-start-options>' . self::ROW . 'name="skipMedia"' . self::ROW . 'name="bypassCache"' . self::ROW . '</template>#s',
            $html
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function skipMediaPreselections(): iterable
    {
        yield 'a configuration skipping media' => [41, true];
        yield 'a configuration importing media' => [50, false];
    }

    #[DataProvider('skipMediaPreselections')]
    #[Test]
    public function indexPreselectsSkipMediaFromTheConfiguration(int $configuration, bool $checked): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="' . $configuration . '">' . self::ROW . '<input[^>]*name="skipMedia"#s',
            $html
        );
        $this->assertSame($checked, (bool)preg_match(
            '#<tr data-configuration="' . $configuration . '">' . self::ROW . '<input[^>]*name="skipMedia"[^>]*checked#s',
            $html
        ));
    }

    /**
     * @return iterable<string, array{int, array<string, string>, array<string, int|string>}>
     */
    public static function starts(): iterable
    {
        yield 'an administrator, skipping media and bypassing the cache' => [
            1,
            ['skipMedia' => '1', 'bypassCache' => '1'],
            ['backend_user' => 1, 'skip_media' => 1, 'bypass_cache' => 1],
        ];
        yield 'an editor granted the module, with media and cache' => [
            3,
            [],
            ['backend_user' => 3, 'skip_media' => 0, 'bypass_cache' => 0],
        ];
    }

    /**
     * @param array<string, string> $options
     * @param array<string, int|string> $expectedJob
     */
    #[DataProvider('starts')]
    #[Test]
    public function startQueuesAManualJobWithTheUserAndTheChosenOptions(
        int $backendUser,
        array $options,
        array $expectedJob,
    ): void {
        $this->setUpBackendUser($backendUser);

        $response = $this->get(ImportController::class)->startAction(
            $this->request->withMethod('POST')->withParsedBody(['configuration' => '41', ...$options])
        );

        $this->assertSame(303, $response->getStatusCode());
        $job = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->select(['*'], 'tx_thuecat_import_job', ['configuration' => 41, 'status' => 'queued'])
            ->fetchAssociative()
        ;
        $this->assertIsArray($job);
        $this->assertEquals(
            ['job_trigger' => 'manual', ...$expectedJob],
            array_intersect_key($job, ['job_trigger' => true, ...$expectedJob])
        );
        $this->assertIsNumeric($job['uid']);
        $this->assertEquals([new ImportStarted((int)$job['uid'])], $this->queuedMessages());
    }

    #[Test]
    public function startQueuesNothingWhileTheConfigurationHasAnOpenJob(): void
    {
        $this->get(ImportController::class)->startAction(
            $this->request->withMethod('POST')->withParsedBody(['configuration' => '32'])
        );

        $this->assertSame(5, $this->countRows('tx_thuecat_import_job'));
        $this->assertSame([], $this->queuedMessages());
    }

    #[Test]
    public function indexOffersAConfirmedStopForAnOpenJob(): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="32">' . self::ROW . 'name="job" value="1"#s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="32">' . self::ROW . 't3js-modal-trigger#s',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '#<tr data-configuration="41">' . self::ROW . 'name="job"#s',
            $html
        );
    }

    #[Test]
    public function stopSetsTheJobToStopping(): void
    {
        $response = $this->get(ImportController::class)->stopAction(
            $this->request->withMethod('POST')->withParsedBody(['job' => '1'])
        );

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_job', ['uid' => 1, 'status' => 'stopping']));
    }

    /**
     * @return iterable<string, array{int, int, int, string}>
     */
    public static function refusals(): iterable
    {
        yield 'without the module permission' => [2, 41, 1, 'running'];
        yield 'without read access to the storage page' => [3, 60, 5, 'queued'];
    }

    #[DataProvider('refusals')]
    #[Test]
    public function startAndStopAreRefused(int $backendUser, int $configuration, int $openJob, string $status): void
    {
        $this->setUpBackendUser($backendUser);

        $this->get(ImportController::class)->startAction(
            $this->request->withMethod('POST')->withParsedBody(['configuration' => (string)$configuration])
        );
        $this->get(ImportController::class)->stopAction(
            $this->request->withMethod('POST')->withParsedBody(['job' => (string)$openJob])
        );

        $this->assertSame(5, $this->countRows('tx_thuecat_import_job'));
        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_job', ['uid' => $openJob, 'status' => $status]));
        $this->assertSame([], $this->queuedMessages());
    }

    #[Test]
    public function indexShowsARunningJobsProgressAsFinishedOutOfAllItems(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleHalfway.csv');

        $this->assertMatchesRegularExpression(
            '#<tr data-job="1"' . self::ROW . 'role="progressbar" aria-valuenow="4" aria-valuemin="0" aria-valuemax="9"' . self::ROW . '4 / 9#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexEstimatesTheRemainingTimeAsTheAverageItemDurationTimesThePendingItems(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleHalfway.csv');

        $this->assertMatchesRegularExpression(
            '#<tr data-job="1"' . self::ROW . 'about 3 min 20 s#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        );
    }

    #[Test]
    public function indexShowsNoEstimateBeforeTheFirstItemHasFinished(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportModuleNoneFinished.csv');

        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $this->assertMatchesRegularExpression('#<tr data-job="1"' . self::ROW . '0 / 3#s', $html);
        $this->assertDoesNotMatchRegularExpression('#<tr data-job="1"' . self::ROW . 'about #s', $html);
    }

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function stalls(): iterable
    {
        yield 'queued for five minutes' => [3, 300, false];
        yield 'queued for more than five minutes' => [3, 301, true];
        yield 'running for five minutes without progress' => [1, 300, false];
        yield 'running for more than five minutes without progress' => [1, 301, true];
    }

    #[DataProvider('stalls')]
    #[Test]
    public function indexMarksAJobStalledAfterFiveMinutesWithoutProgress(int $job, int $secondsSinceProgress, bool $stalled): void
    {
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportConfigurationOverview::class, new ImportConfigurationOverview(
            $this->get(ConnectionPool::class),
            $this->get(SiteFinder::class),
            new FixedClock(new DateTimeImmutable('@' . (self::LAST_PROGRESS + $secondsSinceProgress))),
        ));

        $this->assertSame($stalled, (bool)preg_match(
            '#<tr data-job="' . $job . '"' . self::ROW . 'Stalled#s',
            (string)$this->get(ImportController::class)->indexAction($this->request)->getBody()
        ));
    }

    #[Test]
    public function statusReturnsTheBlockOfEachRequestedConfigurationAsTheIndexRendersIt(): void
    {
        $html = (string)$this->get(ImportController::class)->indexAction($this->request)->getBody();

        $response = $this->get(ImportController::class)->statusAction(
            $this->request->withQueryParams(['configurations' => '41,7'])
        );

        $status = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($status);
        $this->assertIsArray($status['configurations']);
        $this->assertSame([41, 7], array_keys($status['configurations']));
        foreach ($status['configurations'] as $uid => $block) {
            $this->assertIsString($block);
            $this->assertStringStartsWith('<tbody data-configuration-block="' . $uid . '">', trim($block));
            $this->assertStringContainsString(trim($block), $html);
        }
    }

    #[Test]
    public function statusShowsAJobQueuedAfterThePageWasRendered(): void
    {
        $job = $this->get(ImportJobs::class)->queueScheduled(41);

        $response = $this->get(ImportController::class)->statusAction(
            $this->request->withQueryParams(['configurations' => '41'])
        );

        $status = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($status);
        $this->assertIsArray($status['configurations']);
        $this->assertIsString($status['configurations'][41]);
        $this->assertMatchesRegularExpression(
            '#<tr data-configuration="41">' . self::ROW . '<span class="badge badge-info">queued</span>.*<tr data-job="' . $job . '"#s',
            $status['configurations'][41]
        );
    }

    #[Test]
    public function statusLeavesOutConfigurationsTheUserMayNotRead(): void
    {
        $this->setUpBackendUser(3);

        $response = $this->get(ImportController::class)->statusAction(
            $this->request->withQueryParams(['configurations' => '32,41,7,50'])
        );

        $status = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($status);
        $this->assertIsArray($status['configurations']);
        $this->assertSame([32, 41], array_keys($status['configurations']));
    }
}
