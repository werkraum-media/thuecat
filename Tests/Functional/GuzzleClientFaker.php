<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Exception;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use OutOfBoundsException;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GuzzleClientFaker
{
    private static ?MockHandler $mockHandler = null;

    /**
     * Labels for queued responses, in append order. As each response is
     * consumed the matching label is moved from $queuedLabels to $consumed
     * so an empty-queue error can show what got fetched and what's still
     * pending.
     *
     * @var list<string>
     */
    private static array $queuedLabels = [];

    /**
     * @var list<array{label: string, url: string}>
     */
    private static array $consumed = [];

    public static function registerClient(): void
    {
        self::reset();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker'] = function (callable $handler) {
            return self::wrappedHandler();
        };
    }

    /**
     * Cleans things up, call it in tests tearDown() method.
     */
    public static function tearDown(): void
    {
        self::reset();
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker']);
    }

    /**
     * Adds a new response to the stack with defaults, returning the file contents of given file.
     */
    public static function appendResponseFromFile(string $fileName): void
    {
        $fileContent = file_get_contents($fileName);
        if ($fileContent === false) {
            throw new Exception('Could not load file: ' . $fileName, 1656485162);
        }

        self::appendResponseFromContent($fileContent, basename($fileName));
    }

    public static function appendNotFoundResponse(): void
    {
        self::appendResponse(new Response(SymfonyResponse::HTTP_NOT_FOUND), '404');
    }

    public static function appendUnauthorizedResponse(): void
    {
        self::appendResponse(new Response(SymfonyResponse::HTTP_UNAUTHORIZED), '401');
    }

    public static function getLastRequest(): ?RequestInterface
    {
        return self::getMockHandler()->getLastRequest();
    }

    private static function reset(): void
    {
        self::getMockHandler()->reset();
        self::$queuedLabels = [];
        self::$consumed = [];
    }

    private static function appendResponseFromContent(string $content, string $label): void
    {
        self::appendResponse(new Response(
            SymfonyResponse::HTTP_OK,
            [],
            $content
        ), $label);
    }

    private static function getMockHandler(): MockHandler
    {
        if (!self::$mockHandler instanceof MockHandler) {
            self::$mockHandler = new MockHandler();
        }

        return self::$mockHandler;
    }

    /**
     * Wrap MockHandler so we can intercept each request: record the consumed
     * label + URL, and on empty-queue raise a more helpful error than the
     * stock `OutOfBoundsException: Mock queue is empty`.
     */
    private static function wrappedHandler(): callable
    {
        $mock = self::getMockHandler();
        return static function (RequestInterface $request, array $options) use ($mock) {
            if (count($mock) === 0) {
                throw new OutOfBoundsException(self::emptyQueueMessage($request));
            }
            $label = array_shift(self::$queuedLabels) ?? '<unlabeled>';
            self::$consumed[] = ['label' => $label, 'url' => (string)$request->getUri()];
            return $mock($request, $options);
        };
    }

    private static function emptyQueueMessage(RequestInterface $request): string
    {
        $lines = [];
        $lines[] = 'Mock queue is empty.';
        $lines[] = 'Pending request: ' . $request->getMethod() . ' ' . $request->getUri();
        $lines[] = 'Consumed (' . count(self::$consumed) . '):';
        if (self::$consumed === []) {
            $lines[] = '  (none)';
        } else {
            foreach (self::$consumed as $i => $entry) {
                $lines[] = sprintf('  #%d %s  ←  %s', $i + 1, $entry['label'], $entry['url']);
            }
        }
        $lines[] = 'Still queued: ' . (self::$queuedLabels === [] ? '(none)' : implode(', ', self::$queuedLabels));
        return implode("\n", $lines);
    }

    private static function appendResponse(Response $response, string $label): void
    {
        self::getMockHandler()->append($response);
        self::$queuedLabels[] = $label;
    }
}
