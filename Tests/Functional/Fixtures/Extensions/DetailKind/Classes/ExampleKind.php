<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Fixtures\Extensions\DetailKind\Classes;

use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKind;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKindProvider;

final class ExampleKind implements DetailRecordKindProvider
{
    public function getKind(): DetailRecordKind
    {
        return new DetailRecordKind(
            'example',
            'tx_thuecattestdetailkind_example',
            'ThuecatTestDetailKind',
            'ExampleShow',
            'example',
            'thuecat.examples.pid_show',
        );
    }
}
