<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
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
class MediaFileDownloaderTest extends TestCase
{
    #[Test]
    public function returnsNoFileOnTransportFailure(): void
    {
        $httpClient = self::createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willThrowException(
            new class('Connection refused', 1785395634) extends RuntimeException implements ClientExceptionInterface {
            }
        );

        self::assertNull($this->download($httpClient));
    }

    #[Test]
    public function returnsNoFileOnErrorStatus(): void
    {
        self::assertNull($this->download($this->clientReturning(400, '<html>redirect stub</html>')));
    }

    #[Test]
    public function returnsNoFileOnEmptyBody(): void
    {
        self::assertNull($this->download($this->clientReturning(200, '')));
    }

    // The status decides whether a missing image costs its stored reference.
    #[Test]
    public function reportsTheStatusOfAFailedDownload(): void
    {
        $status = null;
        $this->download($this->clientReturning(404, ''), $status);

        self::assertSame(404, $status);
    }

    #[Test]
    public function reportsNoStatusWhenNoResponseArrived(): void
    {
        $httpClient = self::createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willThrowException(
            new class('Connection refused', 1785395634) extends RuntimeException implements ClientExceptionInterface {
            }
        );

        $status = 999;
        $this->download($httpClient, $status);

        self::assertNull($status, 'No response means no status to reason about.');
    }

    #[Test]
    public function sendsTheApiKeyToTheAssetHostThatRequiresIt(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg',
            'secret-key',
            'https://cdb.thuecat.org'
        );

        self::assertSame('api_key=secret-key', $client->lastRequest?->getUri()->getQuery());
    }

    #[Test]
    public function sendsNoApiKeyToOtherHosts(): void
    {
        $client = $this->capturingClient();

        $this->downloadFrom(
            $client,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'secret-key',
            'https://cdb.thuecat.org'
        );

        self::assertSame('', $client->lastRequest?->getUri()->getQuery());
    }

    #[Test]
    public function neverSendsTheJsonLdFormatParameter(): void
    {
        foreach ([
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg',
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        ] as $url) {
            $client = $this->capturingClient();

            $this->downloadFrom($client, $url, 'secret-key', 'https://cdb.thuecat.org');

            self::assertStringNotContainsString(
                'format=',
                (string)$client->lastRequest?->getUri()->getQuery(),
                $url
            );
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
            'https://cdb.thuecat.org'
        );

        $query = (string)$client->lastRequest?->getUri()->getQuery();
        self::assertStringContainsString('v=2', $query);
        self::assertStringContainsString('api_key=secret-key', $query);
    }

    /** The guard the production trace shows failing. */
    #[Test]
    public function reusesTheStagedFileInsteadOfDownloadingItAgain(): void
    {
        $client = $this->capturingClient();
        $staged = self::createStub(File::class);

        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = self::createStub(Folder::class);
        $staging->method('hasFile')->willReturn(true);
        $staging->method('getFile')->willReturn($staged);

        $downloader = new MediaFileDownloader($client);
        $file = $downloader->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        );

        self::assertSame($staged, $file);
        self::assertNull($client->lastRequest, 'A staged file must not be fetched again.');
    }

    /** One URL is one name; two URLs never collide. */
    #[Test]
    public function derivesTheStagedNameFromTheDownloadUrlAlone(): void
    {
        $url = 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image';

        self::assertSame(
            $this->stagedNameFor($url),
            $this->stagedNameFor($url),
            'One asset URL is one staged name.'
        );
        self::assertNotSame(
            $this->stagedNameFor($url),
            $this->stagedNameFor('https://cms.thuecat.org/o/adaptive-media/image/5099197/Preview-1280x0/image'),
            'Two assets whose URLs differ must never collide.'
        );
    }

    #[Test]
    public function separatesTwoAssetsSharingAGenericUrlStem(): void
    {
        self::assertNotSame(
            $this->stagedNameFor('https://cdb.thuecat.org/assets/ttg/a/original/image.jpg'),
            $this->stagedNameFor('https://cdb.thuecat.org/assets/ttg/b/original/image.jpg')
        );
    }

    #[Test]
    public function takesTheFileExtensionFromTheUrl(): void
    {
        self::assertStringEndsWith(
            '.webp',
            $this->stagedNameFor('https://www.kulturcarre.de/media/foo.webp')
        );
    }

    // Extensionless is the adaptive-media shape.
    #[Test]
    public function fallsBackToJpgWhenTheUrlNamesNoExtension(): void
    {
        self::assertStringEndsWith(
            '.jpg',
            $this->stagedNameFor('https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image')
        );
    }

    #[Test]
    public function reusesThePromotedFileInsteadOfDownloadingItAgain(): void
    {
        $client = $this->capturingClient();
        $promoted = self::createStub(File::class);

        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(true);
        $target->method('getFile')->willReturn($promoted);
        $staging = self::createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        $file = (new MediaFileDownloader($client))->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
        );

        self::assertSame($promoted, $file);
        self::assertNull($client->lastRequest, 'A promoted file must not be fetched again.');
    }

    /** Reads the name off the folder the download creates the file in. */
    private function stagedNameFor(string $downloadUrl): string
    {
        $captured = '';

        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = self::createMock(Folder::class);
        $staging->method('hasFile')->willReturn(false);
        $staging->method('createFile')->willReturnCallback(
            function (string $fileName) use (&$captured): File {
                $captured = $fileName;
                return self::createStub(File::class);
            }
        );

        (new MediaFileDownloader($this->clientReturning(200, 'image-bytes')))->download(
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

    private function downloadFrom(
        CapturingClient $client,
        string $downloadUrl,
        string $apiKey,
        string $apiDomain
    ): void {
        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = self::createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);
        $staging->method('createFile')->willReturn(self::createStub(File::class));

        (new MediaFileDownloader($client))->download(
            $target,
            $staging,
            $downloadUrl,
            $apiKey,
            $apiDomain,
        );
    }

    private function clientReturning(int $status, string $body): ImportHttpClient
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);

        $response = self::createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($stream);

        $httpClient = self::createStub(ImportHttpClient::class);
        $httpClient->method('sendRequest')->willReturn($response);

        return $httpClient;
    }

    /**
     * Neither folder holds the file, so download() always reaches the fetch.
     */
    private function download(ImportHttpClient $httpClient, ?int &$failureStatus = null): ?object
    {
        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = self::createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        $failureDetail = null;

        return (new MediaFileDownloader($httpClient))->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image',
            '',
            '',
            $failureDetail,
            $failureStatus,
        );
    }
}
