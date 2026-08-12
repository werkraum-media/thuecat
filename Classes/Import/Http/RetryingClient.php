<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Http;

use Closure;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Retries transient failures. An explicit loop rather than a Guzzle middleware
 * so the decision is testable without a handler stack and each exhausted retry
 * can be reported with its attempt count.
 */
final class RetryingClient implements ImportHttpClient
{
    // Internal, never sent upstream: how many attempts produced this response.
    public const ATTEMPTS_HEADER = 'X-Thuecat-Import-Attempts';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly int $maxAttempts,
        private readonly ?Closure $sleeper = null,
        private readonly ?RetryTally $tally = null,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $attempts = max(1, $this->maxAttempts);
        $lastFailure = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->client->sendRequest($request);
                if ($response->getStatusCode() < 500) {
                    // Only a later attempt is a recovery; exhaustion leaves by
                    // another path and is already reported as a failure.
                    $this->tally?->recordAttempts($attempt);
                    return $response;
                }
                $lastFailure = null;
            } catch (ClientExceptionInterface $failure) {
                $lastFailure = $failure;
                $response = null;
            }

            if ($attempt < $attempts) {
                $this->backOff($attempt);
            }
        }

        // A 5xx still carries a response; callers decide what it means. The
        // count rides along so a caller that does raise can report it.
        if ($response instanceof ResponseInterface) {
            return $response->withHeader(self::ATTEMPTS_HEADER, (string)$attempts);
        }

        throw new RetryExhaustedException(
            sprintf('Giving up on %s after %d attempts.', (string)$request->getUri(), $attempts),
            1786512023,
            $attempts,
            (string)$request->getUri(),
            $lastFailure
        );
    }

    private function backOff(int $attempt): void
    {
        $seconds = 2 ** ($attempt - 1);

        if ($this->sleeper !== null) {
            ($this->sleeper)($seconds);
            return;
        }

        sleep($seconds);
    }
}
