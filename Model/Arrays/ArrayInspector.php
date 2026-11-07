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

use Hryvinskyi\Base\Api\Arrays\ArrayInspectorInterface;
use Traversable;

/**
 * Answers questions about the shape and content of arrays and `Traversable`s.
 */
class ArrayInspector implements ArrayInspectorInterface
{
    /**
     * @inheritdoc
     */
    public function isAssociative(mixed $array, bool $allStrings = true): bool
    {
        if (!is_array($array) || $array === []) {
            return false;
        }

        $stringKeys = count(array_filter(array_keys($array), 'is_string'));

        return $allStrings ? $stringKeys === count($array) : $stringKeys > 0;
    }

    /**
     * @inheritdoc
     */
    public function isIndexed(mixed $array, bool $consecutive = false): bool
    {
        if (!is_array($array)) {
            return false;
        }

        $keys = array_keys($array);
        if ($consecutive) {
            return $keys === array_keys($keys);
        }

        return count(array_filter($keys, 'is_int')) === count($keys);
    }

    /**
     * @inheritdoc
     */
    public function isIn(mixed $needle, mixed $haystack, bool $strict = false): bool
    {
        if (!$this->isTraversable($haystack)) {
            throw new \InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        if (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }

        foreach ($haystack as $value) {
            if ($strict ? $needle === $value : $needle == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isTraversable(mixed $var): bool
    {
        return is_array($var) || $var instanceof Traversable;
    }

    /**
     * @inheritdoc
     */
    public function isSubset(mixed $needles, mixed $haystack, bool $strict = false): bool
    {
        if (!$this->isTraversable($needles)) {
            throw new \InvalidArgumentException('Argument $needles must be an array or implement Traversable');
        }

        foreach ($needles as $needle) {
            if (!$this->isIn($needle, $haystack, $strict)) {
                return false;
            }
        }

        return true;
    }
}
