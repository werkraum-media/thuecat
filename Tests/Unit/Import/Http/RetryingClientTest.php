<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Http;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\Response;
use WerkraumMedia\ThueCat\Import\Http\RetryExhaustedException;
use WerkraumMedia\ThueCat\Import\Http\RetryingClient;
use WerkraumMedia\ThueCat\Import\Http\RetryTally;

class RetryingClientTest extends TestCase
{
    #[Test]
    public function returnsSuccessfulResponseWithoutRetrying(): void
    {
        $client = $this->clientAnswering(new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $client->attempts);
    }

    #[Test]
    public function retriesServerErrorThenSucceeds(): void
    {
        $client = $this->clientAnswering(new Response(null, 503), new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $client->attempts);
    }

    #[Test]
    public function retriesTransportFailureThenSucceeds(): void
    {
        $client = $this->clientAnswering($this->transportFailure(), new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $client->attempts);
    }

    /**
     * A 4xx is a configuration or data fact; repeating it only multiplies load.
     */
    #[Test]
    #[DataProvider('clientErrorStatuses')]
    public function doesNotRetryClientError(int $status): void
    {
        $client = $this->clientAnswering(new Response(null, $status), new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame($status, $response->getStatusCode());
        self::assertSame(1, $client->attempts);
    }

    public static function clientErrorStatuses(): array
    {
        return [
            'unauthorized' => [401],
            'not found' => [404],
            'gone' => [410],
        ];
    }

    #[Test]
    public function stopsAfterConfiguredAttemptsOnRepeatedServerError(): void
    {
        $client = $this->clientAnswering(
            new Response(null, 500),
            new Response(null, 500),
            new Response(null, 500),
            new Response(null, 500)
        );
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(3, $client->attempts);
    }

    /**
     * A transport failure never yields a response, so exhaustion has to raise.
     */
    #[Test]
    public function raisesAfterConfiguredAttemptsOnRepeatedTransportFailure(): void
    {
        $client = $this->clientAnswering(
            $this->transportFailure(),
            $this->transportFailure(),
            $this->transportFailure()
        );
        $subject = new RetryingClient($client, 3, $this->neverSleeps());

        try {
            $subject->sendRequest(new Request('https://example.com/a', 'GET'));
            self::fail('Expected RetryExhaustedException.');
        } catch (RetryExhaustedException $exception) {
            self::assertSame(3, $exception->attempts);
            self::assertSame('https://example.com/a', $exception->url);
        }

        self::assertSame(3, $client->attempts);
    }

    /**
     * MediaFileDownloader catches the PSR-18 marker to skip one image; an
     * exhausted retry must keep landing in that catch.
     */
    #[Test]
    public function exhaustionIsAPsr18ClientException(): void
    {
        $client = $this->clientAnswering($this->transportFailure());
        $subject = new RetryingClient($client, 1, $this->neverSleeps());

        $this->expectException(ClientExceptionInterface::class);

        $subject->sendRequest(new Request('https://example.com/a', 'GET'));
    }

    #[Test]
    public function backsOffBetweenAttempts(): void
    {
        $slept = [];
        $client = $this->clientAnswering(new Response(null, 500), new Response(null, 500), new Response(null, 200));
        $subject = new RetryingClient($client, 3, function (int $seconds) use (&$slept): void {
            $slept[] = $seconds;
        });

        $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertCount(2, $slept, 'One wait per retry, none after the final attempt.');
        self::assertGreaterThan($slept[0], $slept[1], 'Backoff grows.');
    }

    #[Test]
    public function singleAttemptConfigurationNeverRetries(): void
    {
        $client = $this->clientAnswering(new Response(null, 500), new Response(null, 200));
        $subject = new RetryingClient($client, 1, $this->neverSleeps());

        $response = $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(1, $client->attempts);
    }

    private function transportFailure(): ClientExceptionInterface
    {
        return new class('connection reset', 1786512022) extends RuntimeException implements ClientExceptionInterface {
        };
    }

    #[Test]
    public function reportsARecoveredRequestToTheTally(): void
    {
        $tally = new RetryTally();
        $client = $this->clientAnswering(new Response(null, 503), new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps(), $tally);

        $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertSame(1, $tally->recoveredRequests(), 'The recovery is the whole signal.');
        self::assertSame(1, $tally->wastedAttempts());
    }

    #[Test]
    public function reportsNothingWhenTheFirstAttemptSucceeds(): void
    {
        $tally = new RetryTally();
        $client = $this->clientAnswering(new Response(null, 200));
        $subject = new RetryingClient($client, 3, $this->neverSleeps(), $tally);

        $subject->sendRequest(new Request('https://example.com/a', 'GET'));

        self::assertFalse($tally->hasRecoveries(), 'A clean request must leave no trace.');
    }

    /**
     * An exhausted retry is already reported as a failure; counting it as a
     * recovery too would double-report it as both broken and fine.
     */
    #[Test]
    public function doesNotCountAnExhaustedRetryAsRecovered(): void
    {
        $tally = new RetryTally();
        $client = $this->clientAnswering($this->transportFailure(), $this->transportFailure());
        $subject = new RetryingClient($client, 2, $this->neverSleeps(), $tally);

        try {
            $subject->sendRequest(new Request('https://example.com/a', 'GET'));
        } catch (RetryExhaustedException) {
        }

        self::assertFalse($tally->hasRecoveries());
    }

    /**
     * @param ResponseInterface|ClientExceptionInterface ...$answers
     */
    private function clientAnswering(...$answers): CountingClient
    {
        return new CountingClient($answers);
    }

    private function neverSleeps(): Closure
    {
        return static function (int $seconds): void {
        };
    }
}
