<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Settings;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TopLevelEntityInterface;

/**
 * Table → anchor scope, for every top-level entity.
 */
#[Autoconfigure(public: true)]
class AnchorScopeRegistry
{
    /** @var array<string, AnchorScope>|null */
    protected ?array $scopeByTable = null;

    public function __construct(
        #[AutowireLocator(services: 'import.entity')]
        protected readonly ServiceLocator $entities,
    ) {
    }

    /**
     * Null for a table whose entity declares no scope of its own.
     */
    public function forTable(string $table): ?AnchorScope
    {
        return $this->scopeByTable()[$table] ?? null;
    }

    /**
     * Every scope a top-level kind declares, for validating them before a run
     * knows which kinds it will meet.
     *
     * @return list<AnchorScope>
     */
    public function scopes(): array
    {
        $scopes = [];
        foreach ($this->scopeByTable() as $scope) {
            $scopes[$scope->value] ??= $scope;
        }

        return array_values($scopes);
    }

    /**
     * Read statically by service id, which the container sets to the class
     * name: entities are stateful, so none is instantiated for this.
     *
     * @return array<string, AnchorScope>
     */
    protected function scopeByTable(): array
    {
        if ($this->scopeByTable !== null) {
            return $this->scopeByTable;
        }

        $this->scopeByTable = [];
        foreach (array_keys($this->entities->getProvidedServices()) as $id) {
            if (!is_a($id, TopLevelEntityInterface::class, true) || $id::TABLE === '') {
                continue;
            }
            $claimed = $this->scopeByTable[$id::TABLE] ?? null;
            if ($claimed !== null && $claimed->value !== $id::anchorScope()) {
                // Service order would otherwise pick the table's tree.
                throw new RuntimeException(sprintf(
                    'Table "%s" is claimed by the anchor scopes "%s" and "%s" (%s); one table files into one tree.',
                    $id::TABLE,
                    $claimed->value,
                    $id::anchorScope(),
                    $id
                ), 1790929291);
            }
            $this->scopeByTable[$id::TABLE] = new AnchorScope($id::anchorScope());
        }

        return $this->scopeByTable;
    }
}
