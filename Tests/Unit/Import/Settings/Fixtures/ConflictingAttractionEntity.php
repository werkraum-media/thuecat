<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Settings\Fixtures;

use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;

class ConflictingAttractionEntity extends TouristAttractionEntity
{
    public static function anchorScope(): string
    {
        return 'conflicting';
    }
}
