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

use Hryvinskyi\Base\Api\Arrays\ArrayPathWriterInterface;

/**
 * Returns changed copies of arrays: a value written at a key path, a key or matching items removed.
 */
class ArrayPathWriter implements ArrayPathWriterInterface
{
    /**
     * @inheritdoc
     */
    public function setValue(array $array, string|int|array $path, mixed $value): array
    {
        $keys = is_array($path) ? array_values($path) : explode('.', (string)$path);

        return $this->writeAt($array, $keys, $value);
    }

    /**
     * @inheritdoc
     */
    public function remove(array $array, int|string $key): array
    {
        unset($array[$key]);

        return $array;
    }

    /**
     * @inheritdoc
     */
    public function removeValue(array $array, mixed $value): array
    {
        return array_diff_key($array, $this->valuesIdenticalTo($array, $value));
    }

    /**
     * @inheritdoc
     */
    public function valuesIdenticalTo(array $array, mixed $value): array
    {
        $matches = [];
        foreach ($array as $key => $item) {
            if ($item === $value) {
                $matches[$key] = $item;
            }
        }

        return $matches;
    }

    /**
     * Writes the value at the end of the key path, wrapping every value on the way that is not an array.
     *
     * An empty path writes under the empty-string key, which is where PHP stores a `null` key.
     *
     * @param array<array-key,mixed> $node the array to write into
     * @param list<string|int> $keys the keys leading to the value
     * @param mixed $value the value to write
     * @return array<array-key,mixed> the array with the value written
     */
    private function writeAt(array $node, array $keys, mixed $value): array
    {
        $key = array_shift($keys) ?? '';
        if ($keys === []) {
            $node[$key] = $value;
            return $node;
        }

        if (!isset($node[$key])) {
            $node[$key] = [];
        }
        $child = is_array($node[$key]) ? $node[$key] : [$node[$key]];
        $node[$key] = $this->writeAt($child, $keys, $value);

        return $node;
    }
}
