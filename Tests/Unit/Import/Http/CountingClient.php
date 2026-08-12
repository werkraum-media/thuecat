<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Answers a scripted sequence and counts calls, so a test can assert how many
 * attempts the retry policy actually made. The last answer repeats.
 */
final class CountingClient implements ClientInterface
{
    public int $attempts = 0;

    /**
     * @var list<ResponseInterface|ClientExceptionInterface>
     */
    private array $answers;

    /**
     * @param array<array-key, ResponseInterface|ClientExceptionInterface> $answers
     */
    public function __construct(array $answers)
    {
        $this->answers = array_values($answers);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $answer = $this->answers[$this->attempts] ?? end($this->answers);
        $this->attempts++;

        if ($answer instanceof ClientExceptionInterface) {
            throw $answer;
        }

        if (!$answer instanceof ResponseInterface) {
            throw new RuntimeException('Scripted client ran out of answers.', 1786512024);
        }

        return $answer;
    }
}
