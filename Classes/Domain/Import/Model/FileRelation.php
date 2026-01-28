<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

final class FileRelation extends Relation
{
    public function getData(string $identifierOfParent): array|string
    {
        return array_merge($this->data, [
            'uid_foreign' => $identifierOfParent,
        ]);
    }

    public static function createFileRelationFromFileUid(
        int $fileUid,
        string $column,
        string $tablename,
    ): self {
        return new self(
            'sys_file_reference',
            'NEWfilereference' . $fileUid,
            [
                'uid_local' => $fileUid,
                'tablenames' => $tablename,
                'fieldname' => $column,
            ]
        );
    }
}
