<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Error;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Resolver;
use WerkraumMedia\ThueCat\Import\ResolverContext;

// Raises an Error from inside the guarded try, where the catch takes only
// Exception. rekeyRowsAndInjectPid runs within it on every fetched reference.
final class ResolverThrowingErrorStub extends Resolver
{
    protected function rekeyRowsAndInjectPid(
        DataHandlerPayload $payload,
        ResolverContext $context,
        int $depth
    ): void {
        if ($depth > 0) {
            throw new Error('Simulated programming defect while resolving a reference');
        }
        parent::rekeyRowsAndInjectPid($payload, $context, $depth);
    }
}
