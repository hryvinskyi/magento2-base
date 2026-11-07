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

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Merges arrays recursively, the later ones winning.
 *
 * @api
 */
interface ArrayMergerInterface
{
    /**
     * Merges two or more arrays into one recursively; the arrays given are not changed.
     *
     * - An element with a string key overwrites the former one with the same key (unlike
     *   `array_merge_recursive()`), except that two arrays under the same string key are merged recursively.
     * - An element with an integer key is appended when the key is already taken, and kept under its key otherwise.
     * - A value that implements `UnsetArrayValueInterface` removes the key from the result.
     * - A value that implements `ReplaceArrayValueInterface` puts its `getValue()` under the key as it is, instead of
     *   merging recursively.
     * - A `Traversable` is merged entry by entry, and any other object by its public properties.
     *
     * Every argument is an array or an object: anything else, a scalar or `null`, is refused by the signature with a
     * `TypeError` rather than skipped.
     *
     * @param array<array-key,mixed> $first the array merged into
     * @param array<array-key,mixed>|object $others the arrays, `Traversable`s or objects merged from, in order
     * @return array<array-key,mixed> the merged array
     * @throws InvalidParamException when a `Traversable` yields a key that is neither an integer nor a string
     */
    public function merge(array $first, array|object ...$others): array;
}
