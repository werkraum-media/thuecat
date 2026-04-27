<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * URL-keyed mock HTTP handler for the import tests. Each test declares the
 * URLs it expects the importer to fetch (with multiplicity); the faker
 * returns the staged response for each match. Order of fetch is irrelevant —
 * a re-fetch surfaces as either an "unexpected request" error (bag drained)
 * or as an unconsumed expectation at tearDown verification.
 *
 * URL match key strips the volatile query params (`format`, `api_key`) so
 * tests don't have to declare them, but the unstripped URL is still recorded
 * for getLastRequest()-style assertions.
 */
class GuzzleClientFaker
{
    /**
     * Expected URL → FIFO bag of staged responses. Key is the normalised URL
     * (no format/api_key query params); each entry holds the actual Response
     * plus a label for diagnostics.
     *
     * @var array<string, list<array{label: string, response: Response}>>
     */
    private static array $expected = [];

    /**
     * @var list<array{label: string, url: string}>
     */
    private static array $consumed = [];

    private static ?RequestInterface $lastRequest = null;

    public static function registerClient(): void
    {
        self::reset();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker'] = function (callable $handler) {
            return self::wrappedHandler();
        };
    }

    /**
     * Cleans things up, call it in tests tearDown() method. Returns the
     * still-pending expectations so the abstract test case can assert no
     * leftovers (strict mode).
     *
     * @return array<string, list<string>> URL → list of unconsumed labels
     */
    public static function tearDown(): array
    {
        $remaining = [];
        foreach (self::$expected as $url => $bag) {
            if ($bag === []) {
                continue;
            }
            $remaining[$url] = array_map(static fn (array $entry): string => $entry['label'], $bag);
        }
        self::reset();
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker']);
        return $remaining;
    }

    /**
     * Stage one response for one expected URL. Multiple calls for the same URL
     * stack into the URL's bag and are consumed FIFO.
     */
    public static function expectUrl(string $url, Response $response, string $label): void
    {
        self::$expected[self::normaliseUrl($url)][] = [
            'label' => $label,
            'response' => $response,
        ];
    }

    public static function expectFileForUrl(string $url, string $fileName): void
    {
        $fileContent = file_get_contents($fileName);
        if ($fileContent === false) {
            throw new Exception('Could not load file: ' . $fileName, 1656485162);
        }

        self::expectUrl(
            $url,
            new Response(SymfonyResponse::HTTP_OK, [], $fileContent),
            basename($fileName)
        );
    }

    public static function expectNotFoundForUrl(string $url): void
    {
        self::expectUrl($url, new Response(SymfonyResponse::HTTP_NOT_FOUND), '404 ' . $url);
    }

    public static function expectUnauthorizedForUrl(string $url): void
    {
        self::expectUrl($url, new Response(SymfonyResponse::HTTP_UNAUTHORIZED), '401 ' . $url);
    }

    public static function getLastRequest(): ?RequestInterface
    {
        return self::$lastRequest;
    }

    private static function reset(): void
    {
        self::$expected = [];
        self::$consumed = [];
        self::$lastRequest = null;
    }

    private static function wrappedHandler(): callable
    {
        return static function (RequestInterface $request, array $options) {
            self::$lastRequest = $request;
            $url = (string)$request->getUri();
            $key = self::normaliseUrl($url);

            if (!isset(self::$expected[$key]) || self::$expected[$key] === []) {
                throw new UnexpectedFetchException(self::unexpectedMessage($request, $key));
            }

            $entry = array_shift(self::$expected[$key]);
            self::$consumed[] = ['label' => $entry['label'], 'url' => $url];

            return new FulfilledPromise($entry['response']);
        };
    }

    /**
     * Strip the framework-managed query params so URL matching ignores
     * ?format=jsonld and ?api_key=… variants.
     */
    private static function normaliseUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        $query = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $params);
            unset($params['format'], $params['api_key']);
            if ($params !== []) {
                ksort($params);
                $query = '?' . http_build_query($params);
            }
        }

        return $scheme . '://' . $host . $port . $path . $query;
    }

    private static function unexpectedMessage(RequestInterface $request, string $normalisedKey): string
    {
        $lines = [];
        $lines[] = 'Unexpected HTTP request:';
        $lines[] = '  ' . $request->getMethod() . ' ' . $request->getUri();
        $lines[] = '  (normalised: ' . $normalisedKey . ')';
        $lines[] = 'Consumed (' . count(self::$consumed) . '):';
        if (self::$consumed === []) {
            $lines[] = '  (none)';
        } else {
            foreach (self::$consumed as $i => $entry) {
                $lines[] = sprintf('  #%d %s  ←  %s', $i + 1, $entry['label'], $entry['url']);
            }
        }
        $lines[] = 'Still expected:';
        $anyPending = false;
        foreach (self::$expected as $url => $bag) {
            if ($bag === []) {
                continue;
            }
            $anyPending = true;
            $labels = array_map(static fn (array $e): string => $e['label'], $bag);
            $lines[] = sprintf('  %s  ×%d  [%s]', $url, count($bag), implode(', ', $labels));
        }
        if (!$anyPending) {
            $lines[] = '  (none — every staged URL has been consumed)';
        }
        return implode("\n", $lines);
    }
}
