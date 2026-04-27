<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use RuntimeException;

/**
 * Thrown by GuzzleClientFaker when production code fetches a URL the test
 * did not stage (or staged the wrong number of times). Distinct exception
 * class so production catches that swallow generic exceptions don't hide
 * test-side wiring mistakes.
 */
final class UnexpectedFetchException extends RuntimeException
{
}
