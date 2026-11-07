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
 * Decodes JSON text into PHP data, throwing on malformed input.
 *
 * @api
 */
interface JsonDecoderInterface
{
    /**
     * Decodes the given JSON string into a PHP data structure.
     *
     * `null` and an empty string decode to `null` without an error.
     *
     * @param string|null $json the JSON text to decode
     * @param bool $asArray whether JSON objects become associative arrays (`true`) or `stdClass` objects
     * @return mixed the decoded data
     * @throws InvalidParamException when the text is not valid JSON; the code is the `json_last_error()` value
     */
    public function decode(?string $json, bool $asArray = true): mixed;
}
