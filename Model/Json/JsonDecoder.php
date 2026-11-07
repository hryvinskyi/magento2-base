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

namespace Hryvinskyi\Base\Model\Json;

use Hryvinskyi\Base\Api\Json\JsonDecoderInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Decodes JSON through `json_decode()` and turns any decoding error into an exception.
 */
class JsonDecoder implements JsonDecoderInterface
{
    /**
     * @inheritDoc
     */
    public function decode(?string $json, bool $asArray = true): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, $asArray);
        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE) {
            throw new InvalidParamException(json_last_error_msg(), $lastError);
        }

        return $decoded;
    }
}
