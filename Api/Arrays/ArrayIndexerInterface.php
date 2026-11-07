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
 * Re-keys rows (arrays or objects) by a column: an index, a column list, a key-value map.
 *
 * A column is a key name of a row array, a property name of a row object, a dot path into either, or an anonymous
 * function `function($row, $default)` returning the value, read as `ArrayPathReaderInterface::getValue()` reads it.
 *
 * @api
 */
interface ArrayIndexerInterface
{
    /**
     * Indexes and/or groups the rows by a column.
     *
     * Each row is put under the value of `$key`; a later row with the same value replaces the earlier one. A row
     * whose `$key` value is `null`, or every row when `$key` is `null`, is discarded unless `$groups` is given, in
     * which case it is appended to its group without a key. A float key value is used as its string form.
     *
     * `$groups` nests the result: one level per group column, in order.
     *
     * ```php
     * $rows = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $indexer->index($rows, 'id');
     * // ['123' => [...abc row], '345' => [...hgi row]]
     *
     * $indexer->index($rows, null, 'id');
     * // ['123' => [[...abc row]], '345' => [[...def row], [...hgi row]]]
     *
     * $indexer->index($rows, 'data', [function ($row) { return $row['id']; }, 'device']);
     * // ['123' => ['laptop' => ['abc' => [...]]], '345' => ['tablet' => ['def' => [...]], 'smartphone' => [...]]]
     * ```
     *
     * @param iterable<mixed> $array the rows to index or group
     * @param string|int|\Closure|null $key the column whose value keys each row
     * @param string|int|array<array-key,string|int|\Closure>|null $groups the column, or list of columns, to group by
     * @return array<array-key,mixed> the indexed and/or grouped rows
     * @throws \TypeError when a key or group value is neither a scalar nor `null`
     */
    public function index(iterable $array, string|int|\Closure|null $key, string|int|array|null $groups = []): array;

    /**
     * Returns the values of a column of the rows.
     *
     * ```php
     * $indexer->getColumn([['id' => '123'], ['id' => '345']], 'id'); // ['123', '345']
     * ```
     *
     * @param iterable<array-key,mixed> $array the rows to read the column from
     * @param string|int|\Closure $name the column
     * @param bool $keepKeys whether the result keeps the keys of the rows; if not, it is re-indexed from 0
     * @return array<array-key,mixed> the column values
     */
    public function getColumn(iterable $array, string|int|\Closure $name, bool $keepKeys = true): array;

    /**
     * Builds a key-value map from the rows, optionally grouped by a third column.
     *
     * ```php
     * $rows = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     * $indexer->map($rows, 'id', 'name');          // ['123' => 'aaa', '124' => 'bbb', '345' => 'ccc']
     * $indexer->map($rows, 'id', 'name', 'class'); // ['x' => ['123' => 'aaa', '124' => 'bbb'], 'y' => [...]]
     * ```
     *
     * @param array<array-key,mixed> $array the rows to build the map from
     * @param string|\Closure $from the column giving each map key
     * @param string|\Closure $to the column giving each map value
     * @param string|\Closure|null $group the column to group the map by
     * @return array<array-key,mixed> the map, grouped when `$group` is given
     * @throws \TypeError when a key or group value is neither a scalar nor `null`
     */
    public function map(
        array $array,
        string|\Closure $from,
        string|\Closure $to,
        string|\Closure|null $group = null
    ): array;
}
