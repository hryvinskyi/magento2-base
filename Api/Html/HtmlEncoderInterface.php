<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Html;

/**
 * Encodes text for an HTML context and decodes it back, using the UTF-8 charset.
 *
 * @api
 */
interface HtmlEncoderInterface
{
    /**
     * Encodes `&`, `<`, `>`, `"` and `'` as HTML entities; invalid UTF-8 sequences become U+FFFD.
     *
     * Entities already present in the content are encoded again: `&amp;` becomes `&amp;amp;`. A value that is not a
     * string is cast to one first, so `null` and `false` give an empty string.
     *
     * @param string|int|float|bool|\Stringable|null $content the text to encode
     * @return string the encoded text
     */
    public function encode(string|int|float|bool|\Stringable|null $content): string;

    /**
     * Encodes the content as encode() does, except that entities already present are left as they are.
     *
     * `&lt;` and `&eacute;` stay unchanged while a bare `<` becomes `&lt;`. An empty string and `'0'` are returned
     * unchanged. A value that is not a string is cast to one first, so `null` and `false` give an empty string.
     *
     * @param string|int|float|bool|\Stringable|null $content the text to encode
     * @return string the encoded text
     */
    public function encodeKeepingEntities(string|int|float|bool|\Stringable|null $content): string;

    /**
     * Decodes the entities encode() produces (`&amp;`, `&lt;`, `&gt;`, `&quot;`, `&#039;`) back to characters.
     *
     * @param string $content the text to decode
     * @return string the decoded text
     */
    public function decode(string $content): string;
}
