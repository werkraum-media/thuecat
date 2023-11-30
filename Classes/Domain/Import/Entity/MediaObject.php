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

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;

class MediaObject extends Minimum implements MapsToType
{
    /**
     * @var int
     */
    protected $copyrightYear = 0;

    /**
     * @var string
     */
    protected $license = '';

    /**
     * @var string
     */
    protected $licenseAuthor = '';

    /**
     * @var string|ForeignReference
     */
    protected $author;

    /**
     * @var string
     */
    protected $type = '';

    public function getCopyrightYear(): int
    {
        return $this->copyrightYear;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function getLicenseAuthor(): string
    {
        return $this->licenseAuthor;
    }

    /**
     * @return string|ForeignReference
     */
    public function getAuthor()
    {
        return $this->author;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCopyrightYear(string $copyrightYear): void
    {
        $this->copyrightYear = (int)$copyrightYear;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setLicense(string $license): void
    {
        $this->license = $license;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setLicenseAuthor(string $licenseAuthor): void
    {
        $this->licenseAuthor = $licenseAuthor;
    }

    /**
     * @internal for mapping via Symfony component.
     *
     * @param string|ForeignReference $author
     */
    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setType(array $type): void
    {
        if (in_array('schema:ImageObject', $type)) {
            $this->type = 'image';
        }
    }

    public static function getSupportedTypes(): array
    {
        return [
            'schema:MediaObject',
        ];
    }

    public static function getPriority(): int
    {
        return 10;
    }
}
