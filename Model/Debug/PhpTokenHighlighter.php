<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Debug;

use Magento\Framework\ZendEscaper;

/**
 * Renders text as HTML coloured by the PHP tokens it lexes into, in the colours of PHP's own highlighter.
 *
 * Every token's text is HTML-escaped before it is wrapped, so the only markup in the result is the highlighter's
 * own `<pre>`, `<code>` and `<span>` tags, and stripping them and decoding the entities gives back the input, except
 * that invalid UTF-8 bytes become U+FFFD. The input need not be valid PHP: the lexer covers every byte of any text
 * (a `#1` lexes as a comment, an unpaired quote as a string), so nothing is lost.
 */
class PhpTokenHighlighter
{
    /**
     * Colour of the surrounding text.
     */
    private const HTML_COLOUR = '#000000';

    /**
     * Colour of names, variables and numbers.
     */
    private const DEFAULT_COLOUR = '#0000BB';

    /**
     * Colour of keywords, operators and punctuation: every token without a colour of its own.
     */
    private const KEYWORD_COLOUR = '#007700';

    /**
     * Colour of string literals.
     */
    private const STRING_COLOUR = '#DD0000';

    /**
     * Colour of comments.
     */
    private const COMMENT_COLOUR = '#FF8000';

    /**
     * Token kinds that have a colour other than the keyword colour.
     */
    private const TOKEN_COLOURS = [
        T_INLINE_HTML => self::HTML_COLOUR,
        T_COMMENT => self::COMMENT_COLOUR,
        T_DOC_COMMENT => self::COMMENT_COLOUR,
        T_CONSTANT_ENCAPSED_STRING => self::STRING_COLOUR,
        T_ENCAPSED_AND_WHITESPACE => self::STRING_COLOUR,
        34 => self::STRING_COLOUR, // a double quote opening or closing an interpolated string
        T_STRING => self::DEFAULT_COLOUR,
        T_VARIABLE => self::DEFAULT_COLOUR,
        T_LNUMBER => self::DEFAULT_COLOUR,
        T_DNUMBER => self::DEFAULT_COLOUR,
        T_NAME_QUALIFIED => self::DEFAULT_COLOUR,
        T_NAME_FULLY_QUALIFIED => self::DEFAULT_COLOUR,
        T_NAME_RELATIVE => self::DEFAULT_COLOUR,
        T_STRING_VARNAME => self::DEFAULT_COLOUR,
        T_NUM_STRING => self::DEFAULT_COLOUR,
        T_OPEN_TAG => self::DEFAULT_COLOUR,
        T_OPEN_TAG_WITH_ECHO => self::DEFAULT_COLOUR,
        T_CLOSE_TAG => self::DEFAULT_COLOUR,
    ];

    /**
     * Prefix that makes the lexer read the text as PHP code; its open-tag token is dropped from the result.
     */
    private const OPEN_TAG = '<?php ';

    /**
     * @param ZendEscaper $escaper escapes each token's text for the HTML body
     */
    public function __construct(
        private readonly ZendEscaper $escaper
    ) {
    }

    /**
     * Returns the text as highlighted HTML.
     *
     * @param string $text the text to highlight
     * @return string the text, escaped and coloured, inside `<pre><code>`
     */
    public function highlight(string $text): string
    {
        $tokens = \PhpToken::tokenize(self::OPEN_TAG . $text);
        array_shift($tokens);

        $html = '';
        foreach ($tokens as $token) {
            $html .= $this->renderToken($token);
        }

        return '<pre><code style="color: ' . self::HTML_COLOUR . '">' . $html . '</code></pre>';
    }

    /**
     * Returns one token's text, escaped, inside a span of its colour; whitespace is left without a span.
     *
     * @param \PhpToken $token the token to render
     * @return string the token's HTML
     */
    private function renderToken(\PhpToken $token): string
    {
        $text = $this->escaper->escapeHtml($token->text);
        if ($token->id === T_WHITESPACE) {
            return $text;
        }

        $colour = self::TOKEN_COLOURS[$token->id] ?? self::KEYWORD_COLOUR;

        return '<span style="color: ' . $colour . '">' . $text . '</span>';
    }
}
