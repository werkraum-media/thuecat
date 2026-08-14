<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\TransientEntity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\MediaEntity;

/** URL shapes from the -oatour fixtures and live cdb data. */
class MediaEntityTest extends TestCase
{
    #[Test]
    public function prefersTheContentUrlOverTheSizedRendition(): void
    {
        self::assertSame(
            'http://img.oastatic.com/img/16864060/.jpg',
            $this->urlOf([
                'schema:url' => $this->value('http://img.oastatic.com/img/1250/625/16864060/.jpg'),
                'schema:contentUrl' => $this->value('http://img.oastatic.com/img/16864060/.jpg'),
            ])
        );
    }

    // www.kulturcarre.de: the pair differs by format, not size.
    #[Test]
    public function prefersTheContentUrlWhenTheUrlsDifferByFormat(): void
    {
        self::assertSame(
            'https://www.kulturcarre.de/media/a1b2c3d4e5f6/ansicht.webp',
            $this->urlOf([
                'schema:url' => $this->value('https://www.kulturcarre.de/media/a1b2c3d4e5f6/ansicht.jpg'),
                'schema:contentUrl' => $this->value('https://www.kulturcarre.de/media/a1b2c3d4e5f6/ansicht.webp'),
            ])
        );
    }

    // The fallback is load-bearing: this shape occurs upstream.
    #[Test]
    public function fallsBackToTheUrlWhenNoContentUrlIsOffered(): void
    {
        self::assertSame(
            'http://img.oastatic.com/img/22900410/.jpg',
            $this->urlOf(['schema:url' => $this->value('http://img.oastatic.com/img/22900410/.jpg')])
        );
    }

    // Referenced nodes never carry contentUrl (measured 347/347).
    #[Test]
    public function usesTheUrlOfAReferencedMediaResource(): void
    {
        self::assertSame(
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            $this->urlOf([
                'schema:url' => $this->value('https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image'),
            ])
        );
    }

    #[Test]
    public function usesTheContentUrlWhenItIsTheOnlyOneOffered(): void
    {
        self::assertSame(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg',
            $this->urlOf([
                'schema:contentUrl' => $this->value('https://cdb.thuecat.org/assets/ttg/m-tdm/original/foo/bar.jpg'),
            ])
        );
    }

    #[Test]
    public function hasNoUrlWhenNeitherIsOffered(): void
    {
        self::assertSame('', $this->urlOf(['schema:name' => $this->localised('Ohne URL')]));
    }

    /**
     * @param array<string, mixed> $node
     */
    private function urlOf(array $node): string
    {
        $entity = new MediaEntity();
        $entity->configure($node, 'image', 'de');

        return $entity->getUrl();
    }

    /**
     * @return array{'@type': string, '@value': string}
     */
    private function value(string $url): array
    {
        return ['@type' => 'schema:URL', '@value' => $url];
    }

    /**
     * @return array{'@language': string, '@value': string}
     */
    private function localised(string $text): array
    {
        return ['@language' => 'de', '@value' => $text];
    }
}
