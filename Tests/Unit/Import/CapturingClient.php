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
 *
 * Accepts a script of [status, contents, location] tuples; each request
 * consumes the next entry, which lets a test stage a redirect chain. Without a
 * script every request answers 200 with image bytes.
 */
final class CapturingClient implements ImportHttpClient
{
    public ?RequestInterface $lastRequest = null;

    /** @var list<array{0: int, 1: string, 2: string|null}> */
    private array $script;

    /** @param list<array{0: int, 1: string, 2: string|null}> $script */
    public function __construct(array $script = [])
    {
        $this->script = $script;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        [$status, $contents, $location] = array_shift($this->script) ?? [200, 'image-bytes', null];

        $body = new Stream('php://temp', 'rw');
        $body->write($contents);
        $body->rewind();

        $headers = $location === null ? [] : ['location' => [$location]];

        return new Response($body, $status, $headers);
    }
}
