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
 * Keeps or drops parts of a nested array by dot-path rules.
 *
 * @api
 */
interface ArrayFilterInterface
{
    /**
     * Returns the parts of the array the rules keep, without the parts they exclude.
     *
     * ```php
     * $array = ['A' => [1, 2], 'B' => ['C' => 1, 'D' => 2], 'E' => 1];
     * $filter->filter($array, ['A']);         // ['A' => [1, 2]]
     * $filter->filter($array, ['A', 'B.C']);  // ['A' => [1, 2], 'B' => ['C' => 1]]
     * $filter->filter($array, ['B', '!B.C']); // ['B' => ['D' => 2]]
     * ```
     *
     * Exclusions apply after every keep rule, whatever their order. A rule whose path runs through a value that is
     * not an array matches nothing.
     *
     * @param array<array-key,mixed> $array the source array
     * @param array<array-key,mixed> $filters the rules; entries that are neither strings nor integers are ignored:
     * - `var` keeps `$array['var']`;
     * - `var.key` keeps only `$array['var']['key']`;
     * - `!var.key` removes `$array['var']['key']` from the result.
     * @return array<array-key,mixed> the filtered array
     */
    public function filter(array $array, array $filters): array;
}
