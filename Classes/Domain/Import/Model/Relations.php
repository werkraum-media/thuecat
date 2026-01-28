<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

use ArrayIterator;
use Exception;
use Iterator;

final class Relations implements Iterator
{
    private ArrayIterator $iterator;

    public function __construct(
        Relation ... $relations
    ) {
        $this->iterator = new ArrayIterator($relations);
    }

    /**
     * @return InlineRelation[]
     */
    public function getInlineRelations(): iterable
    {
        foreach ($this->iterator as $relation) {
            if ($relation instanceof InlineRelation) {
                yield $relation;
            }
        }
    }

    public function current(): Relation
    {
        $current = $this->iterator->current();
        if ($current instanceof Relation) {
            return $current;
        }

        throw new Exception('This should never happen, relations contained something that is not a Relation.', 1769589323);
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): int
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
