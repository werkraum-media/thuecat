<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser;

class ParserContext
{
    public function __construct(
        public readonly int $importConfigurationUid,
        public readonly string $apiDomain = '',
    ) {
    }
}
