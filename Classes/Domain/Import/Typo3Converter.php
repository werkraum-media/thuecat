<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Domain\Import;

use Exception;
use InvalidArgumentException;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Importer\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\Converter as Typo3ConcreteConverter;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\Registry;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration as Typo3ImportConfiguration;

class Typo3Converter implements Converter
{
    public function __construct(
        private readonly Registry $registry
    ) {
    }

    public function convert(
        MapsToType $mapped,
        ImportConfiguration $configuration,
        string $language
    ): ?Entity {
        if (!$configuration instanceof Typo3ImportConfiguration) {
            throw new InvalidArgumentException('Only supports TYPO3 import configuration.', 1629710386);
        }

        $concreteConverter = $this->registry->getConverterBasedOnType($mapped);
        if ($concreteConverter === null) {
            throw new Exception(
                'No TYPO3 Converter registered for given Entity "' . $mapped::class . '".',
                1628244329
            );
        }

        return $concreteConverter->convert(
            $mapped,
            $configuration,
            $language
        );
    }
}
