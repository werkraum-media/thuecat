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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity;

class TouristAttraction extends Place implements MapsToType
{
    /**
     * @var string
     */
    protected $slogan = '';

    /**
     * @var string
     */
    protected $startOfConstruction = '';

    /**
     * @var string[]
     */
    protected $sanitations = [];

    /**
     * @var string[]
     */
    protected $otherServices = [];

    public function getSlogan(): string
    {
        return $this->slogan;
    }

    public function getStartOfConstruction(): string
    {
        return $this->startOfConstruction;
    }

    /**
     * @return string[]
     */
    public function getSanitations(): array
    {
        return $this->sanitations;
    }

    /**
     * @return string[]
     */
    public function getOtherServices(): array
    {
        return $this->otherServices;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setSlogan(string $slogan): void
    {
        $this->slogan = str_replace('thuecat:', '', $slogan);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setStartOfConstruction(string $startOfConstruction): void
    {
        $this->startOfConstruction = $startOfConstruction;
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $sanitation
     */
    public function setSanitation($sanitation): void
    {
        if (is_string($sanitation)) {
            $sanitation = [$sanitation];
        }

        $this->sanitations = array_map(function (string $sanitation) {
            return str_replace('thuecat:', '', $sanitation);
        }, $sanitation);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $otherService
     */
    public function setOtherService($otherService): void
    {
        if (is_string($otherService)) {
            $otherService = [$otherService];
        }

        $this->otherServices = array_map(function (string $otherService) {
            return str_replace('thuecat:', '', $otherService);
        }, $otherService);
    }

    public static function getSupportedTypes(): array
    {
        return [
            'schema:TouristAttraction',
        ];
    }
}
