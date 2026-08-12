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
            'dms_1',
            'Foo.jpg',
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
    private function download(ImportHttpClient $httpClient): ?object
    {
        $target = self::createStub(Folder::class);
        $target->method('hasFile')->willReturn(false);
        $staging = self::createStub(Folder::class);
        $staging->method('hasFile')->willReturn(false);

        return (new MediaFileDownloader($httpClient))->download(
            $target,
            $staging,
            'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image',
            'dms_5159216',
            'Foo.jpg',
        );
    }
}
