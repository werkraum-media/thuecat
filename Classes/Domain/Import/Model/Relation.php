<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

class Relation
{
    public function __construct(
        private readonly string $tableName,
        private readonly int|string $identifier,
        private readonly array $data,
    ) {
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function getData(string $identifierOfParent): array|string
    {
        return $this->data;
    }

    public static function createFileRelationFromFileUid(int $fileUid, string $column): self
    {
        return new self(
            'sys_file_reference',
                // TODO:
            'new' . ,
            [
                'uid_local' => $fileUid,
                // TODO: use datahandler id of parent entity
                'uid_foreign' => 68,
                // TODO: use datahandler table of parent entity
                'tablenames' => 'tx_news_domain_model_news',
                'fieldname' => $column,
            ]
        );
    }
}
