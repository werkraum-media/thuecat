<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

class Relation
{
    public function __construct(
        private readonly string $tableName,
        private string $identifier,
        private readonly array $data,
    ) {
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getData(string $identifierOfParent, string $storagePid): array|string
    {
        return $this->data;
    }

    public function setImportedTypo3Uid(int $uid): void
    {
        $this->identifier = (string)$uid;
    }
}
