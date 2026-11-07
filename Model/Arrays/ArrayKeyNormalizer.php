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

namespace Hryvinskyi\Base\Model\Arrays;

/**
 * Turns a value into the array key PHP would store it under, refusing values that cannot be a key.
 *
 * Internal to the array services of this module, which share it so a key is cast the same way when it is looked
 * up and when it is written.
 */
class ArrayKeyNormalizer
{
    /**
     * Returns the array key PHP would use for the value: `null` as `''`, a boolean or float as its integer.
     *
     * @param mixed $value the value to use as an array key
     * @return int|string the array key
     * @throws \TypeError when the value is neither a scalar nor `null`
     */
    public function toKey(mixed $value): int|string
    {
        if (is_int($value) || is_string($value)) {
            return $value;
        }
        if ($value === null) {
            return '';
        }
        if (is_bool($value) || is_float($value)) {
            return (int)$value;
        }

        throw new \TypeError(sprintf('Cannot access offset of type %s on array', get_debug_type($value)));
    }
}
