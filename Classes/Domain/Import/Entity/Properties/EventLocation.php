<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Entity\Properties;

class EventLocation
{
    protected string $name = '';

    protected ?Address $address = null;

    protected ?Geo $geo = null;

    protected string $url = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getGeo(): ?Geo
    {
        return $this->geo;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setGeo(Geo $geo): void
    {
        $this->geo = $geo;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
