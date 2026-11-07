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

use Hryvinskyi\Base\Api\Arrays\ArrayFilterInterface;

/**
 * Keeps or drops parts of a nested array by dot-path rules.
 */
class ArrayFilter implements ArrayFilterInterface
{
    /**
     * @inheritdoc
     */
    public function filter(array $array, array $filters): array
    {
        $result = [];
        $excludeFilters = [];

        foreach ($filters as $filter) {
            if (!is_string($filter) && !is_int($filter)) {
                continue;
            }

            if (is_string($filter) && strncmp($filter, '!', 1) === 0) {
                $excludeFilters[] = substr($filter, 1);
                continue;
            }

            $keys = explode('.', (string)$filter);
            $nodeValue = $array;
            foreach ($keys as $key) {
                if (!is_array($nodeValue) || !array_key_exists($key, $nodeValue)) {
                    continue 2;
                }
                $nodeValue = $nodeValue[$key];
            }

            $result = $this->withValueAt($result, $keys, $nodeValue);
        }

        foreach ($excludeFilters as $filter) {
            $result = $this->withoutPath($result, explode('.', $filter));
        }

        return $result;
    }

    /**
     * Returns the array with the value at the key path, replacing every value on the way that is not an array.
     *
     * @param array<array-key,mixed> $node the array to write into
     * @param list<string> $path the keys leading to the value, at least one
     * @param mixed $value the value to write
     * @return array<array-key,mixed> the array with the value written
     */
    private function withValueAt(array $node, array $path, mixed $value): array
    {
        $segment = (string)array_shift($path);
        if ($path === []) {
            $node[$segment] = $value;
            return $node;
        }

        $child = isset($node[$segment]) && is_array($node[$segment]) ? $node[$segment] : [];
        $node[$segment] = $this->withValueAt($child, $path, $value);

        return $node;
    }

    /**
     * Returns the array without the element at the key path, when the whole path exists through arrays.
     *
     * @param array<array-key,mixed> $node the array to remove the element from
     * @param list<string> $path the keys leading to the element
     * @return array<array-key,mixed> the array without the element
     */
    private function withoutPath(array $node, array $path): array
    {
        $key = array_shift($path);
        if ($key === null || !array_key_exists($key, $node)) {
            return $node;
        }

        if ($path === []) {
            unset($node[$key]);
            return $node;
        }

        if (is_array($node[$key])) {
            $node[$key] = $this->withoutPath($node[$key], $path);
        }

        return $node;
    }
}
