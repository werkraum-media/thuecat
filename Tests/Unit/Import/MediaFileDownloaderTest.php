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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use TYPO3\CMS\Core\Resource\Folder;
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
        $httpClient = self::createStub(ClientInterface::class);
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

    private function clientReturning(int $status, string $body): ClientInterface
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);

        $response = self::createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($stream);

        $httpClient = self::createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        return $httpClient;
    }

    /**
     * Neither folder holds the file, so download() always reaches the fetch.
     */
    private function download(ClientInterface $httpClient): ?object
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
