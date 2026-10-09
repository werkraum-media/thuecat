<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
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

    /**
     * Kept apart from the thrown exception: production code may catch that one
     * and log it as data drift, which would let the test pass on an empty import.
     *
     * @var list<string>
     */
    private static array $unexpected = [];

    private static ?RequestInterface $lastRequest = null;

    /**
     * Static, unlike the handler in $GLOBALS: the functional bootstrap rebuilds
     * TYPO3_CONF_VARS per test, which would hide a registration nobody tore down.
     */
    private static bool $registered = false;

    public static function registerClient(): void
    {
        if (self::$registered) {
            throw new RuntimeException(
                'GuzzleClientFaker was registered again without tearDown() in between. Every test that'
                . ' registers it must call tearDown() and fail on what it returns, or its fetches go unchecked.',
                1791532593
            );
        }
        self::$registered = true;
        self::reset();
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker'] = function (callable $handler) {
            return self::wrappedHandler();
        };
    }

    /**
     * Cleans things up, call it in tests tearDown() method. Returns every
     * violated expectation, staged fetches that never happened and fetches
     * nobody staged, so the abstract test case can fail on them (strict mode).
     *
     * @return list<string>
     */
    public static function tearDown(): array
    {
        $problems = [];
        foreach (self::$expected as $url => $bag) {
            if ($bag === []) {
                continue;
            }
            $labels = array_map(static fn (array $entry): string => $entry['label'], $bag);
            $problems[] = sprintf(
                'Expected HTTP fetch never happened: %s  ×%d  [%s]',
                $url,
                count($labels),
                implode(', ', $labels)
            );
        }
        $problems = [...$problems, ...self::$unexpected];
        self::reset();
        self::$registered = false;
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker']);
        return $problems;
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

    /** How many requests actually reached the handler; a cache hit adds none. */
    public static function countConsumed(): int
    {
        return count(self::$consumed);
    }

    private static function reset(): void
    {
        self::$expected = [];
        self::$consumed = [];
        self::$unexpected = [];
        self::$lastRequest = null;
    }

    private static function wrappedHandler(): callable
    {
        return static function (RequestInterface $request, array $options) {
            self::$lastRequest = $request;
            $url = (string)$request->getUri();
            $key = self::normaliseUrl($url);

            if (!isset(self::$expected[$key]) || self::$expected[$key] === []) {
                $message = self::unexpectedMessage($request, $key);
                self::$unexpected[] = $message;
                throw new UnexpectedFetchException($message);
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
