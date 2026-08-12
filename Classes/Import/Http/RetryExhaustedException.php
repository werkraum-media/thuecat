<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Http;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Every attempt failed transiently. Implements the PSR-18 marker so existing
 * catch sites keep treating it as a transport failure.
 */
final class RetryExhaustedException extends RuntimeException implements ClientExceptionInterface
{
    public function __construct(
        string $message,
        int $code,
        public readonly int $attempts = 0,
        public readonly string $url = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
