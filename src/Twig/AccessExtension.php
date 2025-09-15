<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Access\AccessBundle\Twig;

use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @psalm-suppress MissingConstructor
 */
final class AccessExtension extends AbstractExtension
{
    private SqlFormatter $sqlFormatter;

    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('access_prettify_sql', [$this, 'prettifySql'], ['is_safe' => ['html']]),
            new TwigFilter('access_format_sql', [$this, 'formatSql'], ['is_safe' => ['html']]),
        ];
    }

    public function prettifySql(string $sql): string
    {
        /**
         * We create the instance only when needed
         * @psalm-suppress RedundantPropertyInitializationCheck
         */
        $this->sqlFormatter ??= $this->createSqlFormatter();

        return $this->sqlFormatter->highlight($sql);
    }

    public function formatSql(string $sql): string
    {
        /**
         * We create the instance only when needed
         * @psalm-suppress RedundantPropertyInitializationCheck
         */
        $this->sqlFormatter ??= $this->createSqlFormatter();

        return $this->sqlFormatter->format($sql);
    }

    private function createSqlFormatter(): SqlFormatter
    {
        return new SqlFormatter(
            new HtmlHighlighter([
                HtmlHighlighter::HIGHLIGHT_PRE => 'class="highlight highlight-sql"',
                HtmlHighlighter::HIGHLIGHT_QUOTE => 'class="string"',
                HtmlHighlighter::HIGHLIGHT_BACKTICK_QUOTE => 'class="string"',
                HtmlHighlighter::HIGHLIGHT_RESERVED => 'class="keyword"',
                HtmlHighlighter::HIGHLIGHT_BOUNDARY => 'class="symbol"',
                HtmlHighlighter::HIGHLIGHT_NUMBER => 'class="number"',
                HtmlHighlighter::HIGHLIGHT_WORD => 'class="word"',
                HtmlHighlighter::HIGHLIGHT_ERROR => 'class="error"',
                HtmlHighlighter::HIGHLIGHT_COMMENT => 'class="comment"',
                HtmlHighlighter::HIGHLIGHT_VARIABLE => 'class="variable"',
            ]),
        );
    }
}
