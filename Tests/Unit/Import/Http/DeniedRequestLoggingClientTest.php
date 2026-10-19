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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\Response;
use WerkraumMedia\ThueCat\Import\Http\DeniedRequestLoggingClient;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;

final class DeniedRequestLoggingClientTest extends TestCase
{
    /**
     * @return iterable<string, array{int, RequestInterface, string, string}>
     */
    public static function deniedRequests(): iterable
    {
        $inQuery = new Request('https://cdb.thuecat.org/assets/test.jpg?v=2&api_key=test-secret-key', 'GET');
        $inHeader = (new Request('https://widgets.thuecat.org/api/sync/test-channel/list-changed', 'GET'))
            ->withHeader('Authorization', 'APIKEY test-secret-key')
        ;
        $without = new Request('https://cdb.thuecat.org/assets/test.jpg', 'GET');

        foreach ([401, 403] as $status) {
            yield $status . ', key in the query' => [$status, $inQuery, 'https://cdb.thuecat.org/assets/test.jpg?v=2&api_key=REDACTED', 'query'];
            yield $status . ', key in the header' => [$status, $inHeader, 'https://widgets.thuecat.org/api/sync/test-channel/list-changed', 'header'];
            yield $status . ', no key' => [$status, $without, 'https://cdb.thuecat.org/assets/test.jpg', 'none'];
        }
    }

    #[Test]
    #[DataProvider('deniedRequests')]
    public function logsADeniedRequestAsOneWarning(int $status, RequestInterface $request, string $url, string $credential): void
    {
        $logger = new CapturingLogger();

        (new DeniedRequestLoggingClient($this->clientAnswering($status), $logger))->sendRequest($request);

        self::assertCount(1, $logger->records);
        self::assertSame(LogLevel::WARNING, $logger->records[0]['level']);
        self::assertSame(
            ['method' => 'GET', 'url' => $url, 'status' => $status, 'credential' => $credential],
            $logger->records[0]['context']
        );
    }

    #[Test]
    #[DataProvider('deniedRequests')]
    public function neverLogsTheKey(int $status, RequestInterface $request, string $url, string $credential): void
    {
        $logger = new CapturingLogger();

        (new DeniedRequestLoggingClient($this->clientAnswering($status), $logger))->sendRequest($request);

        self::assertNotSame([], $logger->records);
        self::assertStringNotContainsString('test-secret-key', (string)json_encode($logger->records));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function otherStatuses(): iterable
    {
        yield 'success' => [200];
        yield 'not found' => [404];
        yield 'server error' => [500];
    }

    #[Test]
    #[DataProvider('otherStatuses')]
    public function logsNothingForOtherStatuses(int $status): void
    {
        $logger = new CapturingLogger();

        (new DeniedRequestLoggingClient($this->clientAnswering($status), $logger))
            ->sendRequest(new Request('https://cdb.thuecat.org/assets/test.jpg?api_key=test-secret-key', 'GET'))
        ;

        self::assertSame([], $logger->records);
    }

    #[Test]
    public function handsTheResponseOnUnchanged(): void
    {
        $response = new Response('php://temp', 403);
        $client = self::createStub(ImportHttpClient::class);
        $client->method('sendRequest')->willReturn($response);

        self::assertSame(
            $response,
            (new DeniedRequestLoggingClient($client, new CapturingLogger()))
                ->sendRequest(new Request('https://cdb.thuecat.org/assets/test.jpg', 'GET'))
        );
    }

    protected function clientAnswering(int $status): ImportHttpClient
    {
        $client = self::createStub(ImportHttpClient::class);
        $client->method('sendRequest')->willReturn(new Response('php://temp', $status));

        return $client;
    }
}
