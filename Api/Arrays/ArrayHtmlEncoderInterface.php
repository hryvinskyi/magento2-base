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

namespace Hryvinskyi\Base\Api\Arrays;

/**
 * Encodes or decodes the HTML special characters of every string in a nested array.
 *
 * @api
 */
interface ArrayHtmlEncoderInterface
{
    /**
     * Encodes HTML special characters in the strings of the array, recursively.
     *
     * Strings are encoded as `htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` does: quotes included,
     * existing entities encoded again, invalid UTF-8 replaced by U+FFFD. The data is always taken as UTF-8; there is
     * no charset parameter. Values that are neither strings nor arrays are kept as they are.
     *
     * @param array<array-key,mixed> $data the data to encode
     * @param bool $valuesOnly whether only the values are encoded; if not, string keys are encoded too
     * @return array<array-key,mixed> the encoded data
     */
    public function htmlEncode(array $data, bool $valuesOnly = true): array;

    /**
     * Decodes HTML special-character entities in the strings of the array, recursively.
     *
     * Strings are decoded as `htmlspecialchars_decode($s, ENT_QUOTES | ENT_SUBSTITUTE)` does. Values that are
     * neither strings nor arrays are kept as they are.
     *
     * @param array<array-key,mixed> $data the data to decode
     * @param bool $valuesOnly whether only the values are decoded; if not, string keys are decoded too
     * @return array<array-key,mixed> the decoded data
     */
    public function htmlDecode(array $data, bool $valuesOnly = true): array;
}
