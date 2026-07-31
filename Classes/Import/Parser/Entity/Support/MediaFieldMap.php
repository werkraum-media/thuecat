<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\ParkingFacilityEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristInformationEntity;

/**
 * Which field an imported image occupies on a given table. The entities own
 * their table names and their schema; nothing else may hardcode either.
 */
#[Autoconfigure(public: true)]
class MediaFieldMap
{
    /**
     * Entities that import media — each MUST declare MEDIA_FIELDS. An entity
     * absent here stores none.
     *
     * @var list<class-string<\WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface>>
     */
    private const OWNERS = [
        TouristAttractionEntity::class,
        TouristInformationEntity::class,
        ParkingFacilityEntity::class,
        EventEntity::class,
    ];

    /**
     * @var array<string, array<string, string>> table => kind => field
     */
    private readonly array $fieldsByTable;

    public function __construct()
    {
        $map = [];
        foreach (self::OWNERS as $class) {
            $map[$class::TABLE] = $class::MEDIA_FIELDS;
        }
        $this->fieldsByTable = $map;
    }

    /**
     * Target field for one entry, empty when the table stores no media.
     */
    public function fieldFor(string $table, string $kind): string
    {
        return $this->fieldsByTable[$table][$kind] ?? '';
    }

    /**
     * Every field images may occupy on the table, for reaping. Deduplicated:
     * owners may route several kinds into one field.
     *
     * @return list<string>
     */
    public function fieldsFor(string $table): array
    {
        return array_values(array_unique(array_values($this->fieldsByTable[$table] ?? [])));
    }
}
