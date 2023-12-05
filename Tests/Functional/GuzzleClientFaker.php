<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Exception;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GuzzleClientFaker
{
    private static ?MockHandler $mockHandler = null;

    public static function registerClient(): void
    {
        self::getMockHandler()->reset();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['faker'] = function (callable $handler) {
            return self::getMockHandler();
        };
    }

    /**
     * Cleans things up, call it in tests tearDown() method.
     */
    public static function tearDown(): void
    {
        self::getMockHandler()->reset();
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

        self::appendResponseFromContent($fileContent);
    }

    public static function appendNotFoundResponse(): void
    {
        self::appendResponse(new Response(SymfonyResponse::HTTP_NOT_FOUND));
    }

    private static function appendResponseFromContent(string $content): void
    {
        self::appendResponse(new Response(
            SymfonyResponse::HTTP_OK,
            [],
            $content
        ));
    }

    private static function getMockHandler(): MockHandler
    {
        if (!self::$mockHandler instanceof MockHandler) {
            self::$mockHandler = new MockHandler();
        }

        return self::$mockHandler;
    }

    private static function appendResponse(Response $response): void
    {
        self::getMockHandler()->append($response);
    }
}
