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
 * Sorts rows (arrays or objects with the same structure) by one or several columns.
 *
 * @api
 */
interface ArraySorterInterface
{
    /**
     * Returns the rows sorted by one or several columns; the array given is not changed.
     *
     * Rows whose sort columns compare equal keep their original order. Integer keys are renumbered from 0, as
     * `array_multisort()` does; string keys are kept.
     *
     * @param array<array-key,mixed> $array the rows to sort
     * @param string|int|\Closure|array<array-key,string|int|\Closure> $key the column, or list of columns, to sort
     * by: a key name of the row arrays, a property name of the row objects, or an anonymous function
     * `function($row)` returning the value to compare
     * @param int|array<array-key,int> $direction `SORT_ASC` or `SORT_DESC`, or one per column
     * @param int|array<array-key,int> $sortFlag a PHP sort flag (`SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`,
     * `SORT_LOCALE_STRING`, `SORT_NATURAL`, optionally with `SORT_FLAG_CASE`), or one per column
     * @return array<array-key,mixed> the sorted rows
     * @throws \InvalidArgumentException when `$direction` or `$sortFlag` is a list whose length differs from the
     * number of columns
     */
    public function multisort(
        array $array,
        string|int|\Closure|array $key,
        int|array $direction = SORT_ASC,
        int|array $sortFlag = SORT_REGULAR
    ): array;
}
