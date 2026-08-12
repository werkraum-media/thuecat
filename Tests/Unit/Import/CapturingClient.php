<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use WerkraumMedia\ThueCat\Import\Http\ImportHttpClient;

/**
 * Records the outgoing request so a test can assert the URL that was built.
 * The faker cannot: it normalises api_key away before matching.
 */
final class CapturingClient implements ImportHttpClient
{
    public ?RequestInterface $lastRequest = null;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        $body = new Stream('php://temp', 'rw');
        $body->write('image-bytes');
        $body->rewind();

        return new Response($body, 200);
    }
}
