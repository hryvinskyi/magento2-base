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

namespace Hryvinskyi\Base\Api\Json;

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Encodes a PHP value as JSON, converting objects to arrays first and throwing on any encoding error.
 *
 * @api
 */
interface JsonEncoderInterface
{
    /**
     * Encodes the given value into a JSON string.
     *
     * Unlike `json_encode()`, objects are first converted to arrays: `JsonSerializable` through `jsonSerialize()`,
     * an arrayable object through `toArray()`, a date through its properties, generators and other traversables
     * through their items, any other object through its public properties. An object without properties encodes
     * as `{}`, never as `[]`.
     *
     * Data encoded as JSON must be UTF-8 according to the JSON specification; strings with another encoding are
     * reported as an error.
     *
     * @param mixed $value the data to encode
     * @param int $options `json_encode()` flags; pass `JSON_PRETTY_PRINT` among them for human-readable output
     * @return string the JSON text
     * @throws InvalidParamException when the value cannot be encoded; the code is the `json_last_error()` value
     */
    public function encode(mixed $value, int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string;

    /**
     * Encodes the given value into a JSON string that is safe inside an HTML attribute or a `<script>` block.
     *
     * Tags, quotes, apostrophes and ampersands are written as `\u00XX` escapes, and slashes are escaped, so the
     * text can neither close the surrounding element nor end a quoted attribute. Objects are converted as by
     * `encode()`.
     *
     * @param mixed $value the data to encode
     * @return string the HTML-safe JSON text
     * @throws InvalidParamException when the value cannot be encoded; the code is the `json_last_error()` value
     */
    public function htmlEncode(mixed $value): string;
}
