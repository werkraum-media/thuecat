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

namespace WerkraumMedia\ThueCat\Build\PhpCsFixer;

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;
use const T_ABSTRACT;
use const T_ATTRIBUTE;
use const T_CLASS;
use const T_DECLARE;
use const T_ENUM;
use const T_FINAL;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_OPEN_TAG;
use const T_READONLY;
use const T_RETURN;
use const T_TRAIT;

/**
 * This fixer owns the licence header of every PHP file under Classes/ and Tests/.
 *
 * Outside Fixtures/ a file carries exactly one header, directly after declare(); a
 * licence comment anywhere else is removed. Files by the original author keep his
 * copyright line inside that header, as the GPL requires its notices kept intact.
 * Fixtures carry no licence header at all: one found there is removed, and their
 * other comments are left alone.
 *
 * php-cs-fixer runs one rule set over every file it finds, so the scope lives in
 * supports(); excluding paths from the finder would exempt them from every rule.
 */
final class ScopedHeaderCommentFixer implements FixerInterface, WhitespacesAwareFixerInterface
{
    private const HEADER = <<<'TXT'
        This file is part of the TYPO3 CMS extension "thuecat".

        Copyright (C) werkraum-media <https://werkraum-media.de/>

        This program is free software; you can redistribute it and/or modify it
        under the terms of the GNU General Public License as published by the Free
        Software Foundation; either version 2 of the License, or (at your option)
        any later version.

        For the full license text, see the LICENSE file distributed with this
        extension.

        SPDX-License-Identifier: GPL-2.0-or-later
        TXT;

    private const OWNER_LINE = 'Copyright (C) werkraum-media <https://werkraum-media.de/>';

    private const ORIGINAL_AUTHOR_LINE = '/Copyright \(C\) \d{4}(?:-\d{4})? Daniel Siepmann <[^>\n]+>/';

    private const LICENCE_COMMENT = '/Copyright \(C\)|SPDX-License-Identifier/';

    /** A licence header precedes the first declaration; comments past it are code comments. */
    private const DECLARATION_TOKENS = [
        T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION, T_RETURN,
        T_ABSTRACT, T_FINAL, T_READONLY, T_ATTRIBUTE,
    ];

    private readonly HeaderCommentFixer $headerCommentFixer;

    private WhitespacesFixerConfig $whitespacesConfig;

    public function __construct(
        private readonly string $root,
    ) {
        $this->whitespacesConfig = new WhitespacesFixerConfig();
        $this->headerCommentFixer = $this->headerCommentFixer(self::HEADER);
    }

    public function getName(): string
    {
        return 'WerkraumMedia/header_comment';
    }

    public function supports(SplFileInfo $file): bool
    {
        $relative = $this->relativePath($file);

        return $relative !== null
            && (str_starts_with($relative, 'Classes/') || str_starts_with($relative, 'Tests/'));
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->headerCommentFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->headerCommentFixer->isRisky();
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $licenceComments = $this->findLicenceComments($tokens);

        $originalAuthorLine = null;
        foreach ($licenceComments as $index) {
            if (preg_match(self::ORIGINAL_AUTHOR_LINE, $tokens[$index]->getContent(), $match) === 1) {
                $originalAuthorLine = $match[0];
                break;
            }
        }

        $isFixture = str_contains((string)$this->relativePath($file), '/Fixtures/');
        foreach (array_reverse($licenceComments) as $index) {
            if ($isFixture || !$this->isDirectlyAfterDeclare($tokens, $index)) {
                $this->removeComment($tokens, $index);
            }
        }
        $tokens->clearEmptyTokens();

        if ($isFixture) {
            return;
        }

        $header = $originalAuthorLine === null
            ? self::HEADER
            : str_replace(self::OWNER_LINE, self::OWNER_LINE . "\n" . $originalAuthorLine, self::HEADER);
        $this->headerCommentFixer($header)->fix($file, $tokens);
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->headerCommentFixer->getDefinition();
    }

    public function getPriority(): int
    {
        return $this->headerCommentFixer->getPriority();
    }

    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        $this->whitespacesConfig = $config;
        $this->headerCommentFixer->setWhitespacesConfig($config);
    }

    private function headerCommentFixer(string $header): HeaderCommentFixer
    {
        $fixer = new HeaderCommentFixer();
        $fixer->configure([
            'header' => $header,
            'comment_type' => 'comment',
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ]);
        $fixer->setWhitespacesConfig($this->whitespacesConfig);

        return $fixer;
    }

    /** @return list<int> */
    private function findLicenceComments(Tokens $tokens): array
    {
        $indices = [];
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(self::DECLARATION_TOKENS)) {
                break;
            }
            if ($token->isComment() && preg_match(self::LICENCE_COMMENT, $token->getContent()) === 1) {
                $indices[] = $index;
            }
        }

        return $indices;
    }

    private function isDirectlyAfterDeclare(Tokens $tokens, int $index): bool
    {
        $previous = $tokens->getPrevMeaningfulToken($index);
        if ($previous === null) {
            return false;
        }

        $declare = $tokens->getPrevTokenOfKind($previous, [[T_DECLARE]]);
        if ($declare === null) {
            // Without declare() the header belongs directly after the opening tag.
            return $tokens[$previous]->isGivenKind(T_OPEN_TAG);
        }

        return $tokens->getNextTokenOfKind($declare, [';']) === $previous;
    }

    /**
     * Takes the whitespace after the comment along, so the lines on either side of it
     * close up instead of leaving an extra blank line.
     */
    private function removeComment(Tokens $tokens, int $index): void
    {
        $tokens->clearAt($index);
        $next = $index + 1;
        if (isset($tokens[$next]) && $tokens[$next]->isWhitespace()) {
            $tokens->clearAt($next);
        }
    }

    private function relativePath(SplFileInfo $file): ?string
    {
        $path = str_replace('\\', '/', $file->getPathname());
        $root = rtrim(str_replace('\\', '/', $this->root), '/') . '/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : null;
    }
}
