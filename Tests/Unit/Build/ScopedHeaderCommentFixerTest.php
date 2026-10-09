<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Build;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use WerkraumMedia\ThueCat\Build\PhpCsFixer\ScopedHeaderCommentFixer;

class ScopedHeaderCommentFixerTest extends TestCase
{
    private const HEADER = <<<'TXT'
        /*
         * This file is part of the TYPO3 CMS extension "thuecat".
         *
         * Copyright (C) werkraum-media <https://werkraum-media.de/>
         *
         * This program is free software; you can redistribute it and/or modify it
         * under the terms of the GNU General Public License as published by the Free
         * Software Foundation; either version 2 of the License, or (at your option)
         * any later version.
         *
         * For the full license text, see the LICENSE file distributed with this
         * extension.
         *
         * SPDX-License-Identifier: GPL-2.0-or-later
         */
        TXT;

    private const DANIEL = ' * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>';

    #[Test]
    public function insertsTheHeaderAfterDeclare(): void
    {
        self::assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\n" . self::HEADER . "\n\nnamespace Foo;\n\nclass Bar\n{\n}\n",
            $this->fix('Classes/Bar.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Foo;\n\nclass Bar\n{\n}\n")
        );
    }

    #[Test]
    public function replacesAnOutdatedHeader(): void
    {
        $outdated = "/*\n * Copyright (C) 2026 werkraum-media\n *\n * This program is free software.\n */";

        self::assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\n" . self::HEADER . "\n\nnamespace Foo;\n",
            $this->fix('Classes/Bar.php', "<?php\n\ndeclare(strict_types=1);\n\n" . $outdated . "\n\nnamespace Foo;\n")
        );
    }

    #[Test]
    public function movesAHeaderBelowTheNamespaceAndKeepsTheOriginalAuthor(): void
    {
        $misplaced = "/*\n" . self::DANIEL . "\n *\n * This program is free software.\n */";

        self::assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\n" . $this->headerWithDaniel() . "\n\nnamespace Foo;\n\nuse Baz;\n",
            $this->fix('Tests/Unit/BarTest.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Foo;\n\n" . $misplaced . "\n\nuse Baz;\n")
        );
    }

    #[Test]
    public function leavesAHeaderWithTheOriginalAuthorAlone(): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\n" . $this->headerWithDaniel() . "\n\nnamespace Foo;\n";

        self::assertSame($code, $this->fix('Classes/Bar.php', $code));
    }

    #[Test]
    public function removesTheHeaderFromFixturesAndKeepsTheirOtherComments(): void
    {
        $explanation = "/*\n * Two rows, one per language.\n */";

        self::assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\n" . $explanation . "\n\nreturn [];\n",
            $this->fix(
                'Tests/Functional/Fixtures/Rows.php',
                "<?php\n\ndeclare(strict_types=1);\n\n" . self::HEADER . "\n\n" . $explanation . "\n\nreturn [];\n"
            )
        );
    }

    private function fix(string $relativePath, string $code): string
    {
        $fixer = new ScopedHeaderCommentFixer('/project');
        $fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
        $file = new SplFileInfo('/project/' . $relativePath);
        self::assertTrue($fixer->supports($file), 'Precondition: the fixer handles ' . $relativePath);

        Tokens::clearCache();
        $tokens = Tokens::fromCode($code);
        $fixer->fix($file, $tokens);

        return $tokens->generateCode();
    }

    private function headerWithDaniel(): string
    {
        return str_replace(
            " * Copyright (C) werkraum-media <https://werkraum-media.de/>\n",
            " * Copyright (C) werkraum-media <https://werkraum-media.de/>\n" . self::DANIEL . "\n",
            self::HEADER
        );
    }
}
