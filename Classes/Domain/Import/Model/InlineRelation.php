<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

/**
 * Defines that the relation is "inline".
 * Stored within another record, e.g. multiple sys_file_relation are combined into `images` columns of an attraction.
 * Each relation needs to return the column, e.g. `images` and value, e.g. `NEW34343`.
 */
interface InlineRelation
{
    public function getInlineColumnName(): string;
    public function getInlineColumnValue(): string;
}
