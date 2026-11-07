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

use Hryvinskyi\Base\Api\Arrays\ArrayIndexerInterface;
use Hryvinskyi\Base\Api\Arrays\ArrayPathReaderInterface;

/**
 * Re-keys rows by a column: indexes, column lists and key-value maps.
 */
class ArrayIndexer implements ArrayIndexerInterface
{
    /**
     * @param ArrayPathReaderInterface $reader reads each column value out of a row
     * @param ArrayKeyNormalizer $keys casts a column value to the key it is stored under
     */
    public function __construct(
        private readonly ArrayPathReaderInterface $reader,
        private readonly ArrayKeyNormalizer $keys
    ) {
    }

    /**
     * @inheritdoc
     */
    public function index(iterable $array, string|int|\Closure|null $key, string|int|array|null $groups = []): array
    {
        $result = [];
        $groups = (array)$groups;

        foreach ($array as $element) {
            $path = [];
            foreach ($groups as $group) {
                $path[] = $this->keys->toKey($this->reader->getValue($element, $group));
            }
            $group = &$this->groupNode($result, $path);

            if ($key === null) {
                if ($groups !== []) {
                    $group[] = $element;
                }
                unset($group);
                continue;
            }

            $value = $this->reader->getValue($element, $key);
            if ($value !== null) {
                $group[$this->keys->toKey(is_float($value) ? (string)$value : $value)] = $element;
            }
            unset($group);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getColumn(iterable $array, string|int|\Closure $name, bool $keepKeys = true): array
    {
        $result = [];
        if (!$keepKeys) {
            // a Traversable may yield one key twice: each row still gets its own entry
            foreach ($array as $element) {
                $result[] = $this->reader->getValue($element, $name);
            }
            return $result;
        }

        foreach ($array as $k => $element) {
            $result[$k] = $this->reader->getValue($element, $name);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function map(
        array $array,
        string|\Closure $from,
        string|\Closure $to,
        string|\Closure|null $group = null
    ): array {
        if (is_string($from) && is_string($to) && $group === null
            && strpos($from, '.') === false && strpos($to, '.') === false
        ) {
            return array_column($array, $to, $from);
        }

        $result = [];
        foreach ($array as $element) {
            $key = $this->keys->toKey($this->reader->getValue($element, $from));
            $value = $this->reader->getValue($element, $to);
            if ($group === null) {
                $result[$key] = $value;
                continue;
            }

            $groupKey = $this->keys->toKey($this->reader->getValue($element, $group));
            if (!isset($result[$groupKey]) || !is_array($result[$groupKey])) {
                $result[$groupKey] = [];
            }
            $result[$groupKey][$key] = $value;
        }

        return $result;
    }

    /**
     * Returns a reference to the nested array at the key path, creating the arrays missing on the way.
     *
     * A value on the path that is not an array is replaced by an empty array. The reference lets every row be
     * added in place, so indexing stays linear however large a group grows.
     *
     * @param array<array-key,mixed> $node the array to descend into
     * @param list<int|string> $path the keys to descend through
     * @return array<array-key,mixed> the nested array, by reference
     */
    private function &groupNode(array &$node, array $path): array
    {
        if ($path === []) {
            return $node;
        }

        $segment = array_shift($path);
        if (!isset($node[$segment]) || !is_array($node[$segment])) {
            $node[$segment] = [];
        }

        return $this->groupNode($node[$segment], $path);
    }
}
