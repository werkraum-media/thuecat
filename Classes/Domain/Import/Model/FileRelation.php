<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

use Exception;

final class FileRelation extends Relation implements InlineRelation
{
    public function __construct(
        string $tableName,
        string $identifier,
        array $data,
        private readonly string $inlineColumn,
    ) {
        parent::__construct($tableName, $identifier, $data);
    }

    public function getData(string $identifierOfParent, string $storagePid): array|string
    {
        $data = parent::getData($identifierOfParent, $storagePid);
        if (is_array($data) === false) {
            throw new Exception('file relations only work with array.', 1769588918);
        }

        return array_merge($data, [
            'pid' => $storagePid,
            'uid_foreign' => $identifierOfParent,
            'tablenames' => $this->getTableName(),
            'fieldname' => $this->inlineColumn,
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
            ],
            $column,
        );
    }

    public function getInlineColumnName(): string
    {
        return $this->inlineColumn;
    }

    public function getInlineColumnValue(): string
    {
        return $this->getIdentifier();
    }
}
