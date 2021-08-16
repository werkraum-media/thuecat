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

    /**
     * @var string[]
     */
    protected $museumServices = [];

    /**
     * @var string[]
     */
    protected $architecturalStyles = [];

    /**
     * @var string[]
     */
    protected $trafficInfrastructures = [];

    /**
     * @var string[]
     */
    protected $paymentsAccepted = [];

    /**
     * @var string[]
     */
    protected $digitalOffers = [];

    /**
     * @var string[]
     */
    protected $photographies = [];

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
     * @return string[]
     */
    public function getMuseumServices(): array
    {
        return $this->museumServices;
    }

    /**
     * @return string[]
     */
    public function getArchitecturalStyles(): array
    {
        return $this->architecturalStyles;
    }

    /**
     * @return string[]
     */
    public function getTrafficInfrastructures(): array
    {
        return $this->trafficInfrastructures;
    }

    /**
     * @return string[]
     */
    public function getPaymentsAccepted(): array
    {
        return $this->paymentsAccepted;
    }

    /**
     * @return string[]
     */
    public function getDigitalOffers(): array
    {
        return $this->digitalOffers;
    }

    /**
     * @return string[]
     */
    public function getPhotographies(): array
    {
        return $this->photographies;
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

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $museumService
     */
    public function setMuseumService($museumService): void
    {
        if (is_string($museumService)) {
            $museumService = [$museumService];
        }

        $this->museumServices = array_map(function (string $museumService) {
            return str_replace('thuecat:', '', $museumService);
        }, $museumService);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $architecturalStyle
     */
    public function setArchitecturalStyle($architecturalStyle): void
    {
        if (is_string($architecturalStyle)) {
            $architecturalStyle = [$architecturalStyle];
        }

        $this->architecturalStyles = array_map(function (string $architecturalStyle) {
            return str_replace('thuecat:', '', $architecturalStyle);
        }, $architecturalStyle);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $trafficInfrastructure
     */
    public function setTrafficInfrastructure($trafficInfrastructure): void
    {
        if (is_string($trafficInfrastructure)) {
            $trafficInfrastructure = [$trafficInfrastructure];
        }

        $this->trafficInfrastructures = array_map(function (string $trafficInfrastructure) {
            return str_replace('thuecat:', '', $trafficInfrastructure);
        }, $trafficInfrastructure);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $paymentAccepted
     */
    public function setPaymentAccepted($paymentAccepted): void
    {
        if (is_string($paymentAccepted)) {
            $paymentAccepted = [$paymentAccepted];
        }

        $this->paymentsAccepted = array_map(function (string $paymentAccepted) {
            return str_replace('thuecat:', '', $paymentAccepted);
        }, $paymentAccepted);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $digitalOffer
     */
    public function setDigitalOffer($digitalOffer): void
    {
        if (is_string($digitalOffer)) {
            $digitalOffer = [$digitalOffer];
        }

        $this->digitalOffers = array_map(function (string $digitalOffer) {
            return str_replace('thuecat:', '', $digitalOffer);
        }, $digitalOffer);
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $photography
     */
    public function setPhotography($photography): void
    {
        if (is_string($photography)) {
            $photography = [$photography];
        }

        $this->photographies = array_map(function (string $photography) {
            return str_replace('thuecat:', '', $photography);
        }, $photography);
    }

    public static function getSupportedTypes(): array
    {
        return [
            'schema:TouristAttraction',
        ];
    }
}
