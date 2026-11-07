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
use Hryvinskyi\Base\Api\Arrays\ArraySorterInterface;

/**
 * Sorts rows by one or several columns, keeping the original order of rows that compare equal.
 */
class ArraySorter implements ArraySorterInterface
{
    /**
     * @param ArrayIndexerInterface $indexer reads the column each sort key compares
     */
    public function __construct(
        private readonly ArrayIndexerInterface $indexer
    ) {
    }

    /**
     * @inheritdoc
     */
    public function multisort(
        array $array,
        string|int|\Closure|array $key,
        int|array $direction = SORT_ASC,
        int|array $sortFlag = SORT_REGULAR
    ): array {
        $keys = is_array($key) ? array_values($key) : [$key];
        if ($keys === [] || $array === []) {
            return $array;
        }
        $n = count($keys);
        if (is_int($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new \InvalidArgumentException(
                'The length of $direction parameter must be the same as that of $keys.'
            );
        }
        if (is_int($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new \InvalidArgumentException(
                'The length of $sortFlag parameter must be the same as that of $keys.'
            );
        }
        $direction = array_values($direction);
        $sortFlag = array_values($sortFlag);

        $firstColumn = $this->indexer->getColumn($array, $keys[0]);
        $args = [$direction[0], $sortFlag[0]];
        foreach (array_slice($keys, 1, null, true) as $i => $k) {
            $args[] = $this->indexer->getColumn($array, $k);
            $args[] = $direction[$i];
            $args[] = $sortFlag[$i];
        }

        // the original position breaks ties: without it, rows equal on every column are compared themselves,
        // which fails on objects that reference themselves ("Nesting level too deep")
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        $args[] = &$array;
        array_multisort($firstColumn, ...$args);

        return $array;
    }
}
