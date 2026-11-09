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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;
use WerkraumMedia\ThueCat\Import\MediaFileDownloader;

/**
 * Covers the failure modes the functional suite cannot stage: a transport
 * failure never reaches the faker, which only distinguishes staged responses
 * from unexpected requests.
 */
final class MediaFileDownloaderTest extends TestCase
{
    #[Test]
    public function returnsNoFileOnTransportFailure(): void
    {
        $httpClient = $this->createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willThrowException(
            new class('Connection refused', 1785395634) extends RuntimeException implements ClientExceptionInterface {
            }
        );

        $this->assertNull($this->download($httpClient));
    }

    #[Test]
    public function returnsNoFileOnErrorStatus(): void
    {
        $this->assertNull($this->download($this->clientReturning(400, '<html>redirect stub</html>')));
    }

    #[Test]
    public function returnsNoFileOnEmptyBody(): void
    {
        $this->assertNull($this->download($this->clientReturning(200, '')));
    }

    // The status decides whether a missing image costs its stored reference.
    #[Test]
    public function reportsTheStatusOfAFailedDownload(): void
    {
        $status = null;
        $this->download($this->clientReturning(404, ''), $status);

        $this->assertSame(404, $status);
    }

    #[Test]
    public function reportsNoStatusWhenNoResponseArrived(): void
    {
        $httpClient = $this->createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willThrowException(
            new class('Connection refused', 1785395634) extends RuntimeException implements ClientExceptionInterface {
            }
        );

        $status = 999;
        $this->download($httpClient, $status);

        $this->assertNull($status, 'No response means no status to reason about.');
    }

    #[Test]
    public function sendsTheApiKeyToTheAssetHostThatRequiresIt(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg',
            'secret-key',
            ['cdb.thuecat.org']
        );

        $this->assertSame('api_key=secret-key', $client->lastRequest?->getUri()->getQuery());
    }

    #[Test]
    public function sendsNoApiKeyToOtherHosts(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'secret-key',
            ['cdb.thuecat.org']
        );

        $this->assertSame('', $client->lastRequest?->getUri()->getQuery());
    }

    #[Test]
    public function sendsNoApiKeyToTheResourceHost(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://thuecat.org/resources/test-image.jpg',
            'secret-key',
            ['cdb.thuecat.org']
        );

        $this->assertSame('', $client->lastRequest?->getUri()->getQuery());
    }

    #[Test]
    public function neverSendsTheJsonLdFormatParameter(): void
    {
        foreach ([
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg',
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        ] as $url) {
            $client = $this->capturingClient();

            $this->downloadFrom($client, $url, 'secret-key', ['cdb.thuecat.org']);

            $this->assertStringNotContainsString('format=', (string)$client->lastRequest?->getUri()->getQuery(), $url);
        }
    }

    #[Test]
    public function keepsQueryParametersTheAssetUrlAlreadyCarries(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://cdb.thuecat.org/assets/foo/bar.jpg?v=2',
            'secret-key',
            ['cdb.thuecat.org']
        );

        $query = (string)$client->lastRequest?->getUri()->getQuery();
        $this->assertStringContainsString('v=2', $query);
        $this->assertStringContainsString('api_key=secret-key', $query);
    }

    /** The guard the production trace shows failing. */
    #[Test]
    public function reusesTheStagedFileInsteadOfDownloadingItAgain(): void
    {
        $client = $this->capturingClient();
        $staged = $this->createStub(File::class);

        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(true);
        $staging->method('getFile')->willReturn($staged);

        $downloader = new MediaFileDownloader($client);
        $file = $downloader->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        );

        $this->assertSame($staged, $file);
        $this->assertNull($client->lastRequest, 'A staged file must not be fetched again.');
    }

    /** One URL is one name; two URLs never collide. */
    #[Test]
    public function derivesTheStagedNameFromTheDownloadUrlAlone(): void
    {
        $url = 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image';

        $this->assertSame($this->stagedNameFor($url), $this->stagedNameFor($url), 'One asset URL is one staged name.');
        $this->assertNotSame($this->stagedNameFor($url), $this->stagedNameFor('https://cms.thuecat.org/o/adaptive-media/image/5099197/Preview-1280x0/image'), 'Two assets whose URLs differ must never collide.');
    }

    #[Test]
    public function separatesTwoAssetsSharingAGenericUrlStem(): void
    {
        $this->assertNotSame($this->stagedNameFor('https://cdb.thuecat.org/assets/ttg/a/original/image.jpg'), $this->stagedNameFor('https://cdb.thuecat.org/assets/ttg/b/original/image.jpg'));
    }

    #[Test]
    public function takesTheFileExtensionFromTheUrl(): void
    {
        $this->assertStringEndsWith('.webp', $this->stagedNameFor('https://www.kulturcarre.de/media/foo.webp'));
    }

    // Extensionless is the adaptive-media shape.
    #[Test]
    public function fallsBackToJpgWhenTheUrlNamesNoExtension(): void
    {
        $this->assertStringEndsWith('.jpg', $this->stagedNameFor('https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image'));
    }

    #[Test]
    public function reusesThePromotedFileInsteadOfDownloadingItAgain(): void
    {
        $client = $this->capturingClient();
        $promoted = $this->createStub(File::class);

        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(true);
        $target->method('getFile')->willReturn($promoted);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        $file = new MediaFileDownloader($client)->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        );

        $this->assertSame($promoted, $file);
        $this->assertNull($client->lastRequest, 'A promoted file must not be fetched again.');
    }

    /** Reads the name off the folder the download creates the file in. */
    private function stagedNameFor(string $downloadUrl): string
    {
        $captured = '';

        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);
        $staging->method('createFile')->willReturnCallback(
            function (string $fileName) use (&$captured): File {
                $captured = $fileName;
                return self::createStub(File::class);
            }
        );

        new MediaFileDownloader($this->clientReturning(200, 'image-bytes'))->download(
            $target,
            $staging,
            $downloadUrl,
        );

        return $captured;
    }

    private function capturingClient(): CapturingClient
    {
        return new CapturingClient();
    }

    /**
     * @param list<string> $keyedHosts
     */
    protected function downloadFrom(
        CapturingClient $client,
        string $downloadUrl,
        string $apiKey,
        array $keyedHosts
    ): void {
        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);
        $staging->method('createFile')->willReturn($this->createStub(File::class));

        new MediaFileDownloader($client)->download(
            $target,
            $staging,
            $downloadUrl,
            $apiKey,
            $keyedHosts,
        );
    }

    private function clientReturning(int $status, string $body): ImportHttpClient
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($stream);

        $httpClient = $this->createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willReturn($response);

        return $httpClient;
    }

    /**
     * PSR-18 forbids sendRequest() from following 301 redirect, so the downloader must.
     */
    #[Test]
    public function followsARedirectToTheRealAsset(): void
    {
        $client = $this->clientScripted([
            [301, '', 'https://img.oastatic.com/img2/24836090/default/variant.jpg'],
            [200, 'image-bytes', null],
        ]);

        $captured = '';
        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);
        $staging->method('createFile')->willReturnCallback(
            function (string $fileName) use (&$captured): File {
                $captured = $fileName;
                return self::createStub(File::class);
            }
        );

        $file = new MediaFileDownloader($client)->download(
            $target,
            $staging,
            'http://img.oastatic.com/img/24836090/.jpg',
        );

        $this->assertInstanceOf(File::class, $file, 'A redirected asset must still be downloaded.');
        $this->assertSame('https://img.oastatic.com/img2/24836090/default/variant.jpg', (string)$client->lastRequest?->getUri(), 'The second request must go to the Location target.');
        // Identity stays the URL the payload delivered, so re-imports still match.
        $this->assertSame($this->stagedNameFor('http://img.oastatic.com/img/24836090/.jpg'), $captured);
    }

    #[Test]
    public function stopsRatherThanFollowingARedirectWithoutALocation(): void
    {
        $this->assertNull($this->download($this->clientReturning(301, '')));
    }

    #[Test]
    public function doesNotFollowRedirectsForever(): void
    {
        $client = $this->clientScripted([
            [301, '', 'https://img.oastatic.com/a.jpg'],
            [301, '', 'https://img.oastatic.com/b.jpg'],
            [301, '', 'https://img.oastatic.com/c.jpg'],
            [301, '', 'https://img.oastatic.com/d.jpg'],
            [301, '', 'https://img.oastatic.com/e.jpg'],
            [301, '', 'https://img.oastatic.com/f.jpg'],
        ]);

        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        $this->assertNull(new MediaFileDownloader($client)->download(
            $target,
            $staging,
            'http://img.oastatic.com/img/1/.jpg',
        ));
    }

    /**
     * Responses in order, each as [status, body, location].
     *
     * @param list<array{0: int, 1: string, 2: string|null}> $script
     */
    private function clientScripted(array $script): CapturingClient
    {
        return new CapturingClient($script);
    }

    /**
     * Neither folder holds the file, so download() always reaches the fetch.
     */
    private function download(ImportHttpClient $httpClient, ?int &$failureStatus = null): ?object
    {
        $target = $this->createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = $this->createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        $failureDetail = null;

        return new MediaFileDownloader($httpClient)->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image',
            '',
            [],
            $failureDetail,
            $failureStatus,
        );
    }
}
