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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

/**
 * An entity that can be imported as a root. Such a kind owns a category tree
 * of its own, so it must name the scope its anchors are read under; every
 * other kind is reached only as a relation and resolves under the default
 * scope.
 *
 * The scope is declared, never derived from the class: the names integrators
 * configured do not follow one (TouristAttraction is `thuecat`, Trail is
 * `trails`). Static, so it is readable without instantiating the stateful
 * entity.
 */
interface TopLevelEntityInterface extends EntityInterface
{
    /**
     * @return non-empty-string
     */
    public static function anchorScope(): string;
}
