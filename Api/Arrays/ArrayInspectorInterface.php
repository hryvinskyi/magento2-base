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
 * Answers questions about the shape and content of an array or `Traversable`.
 *
 * @api
 */
interface ArrayInspectorInterface
{
    /**
     * Tells whether the value is an associative array.
     *
     * An array is associative when all its keys are strings, or, with `$allStrings` off, when at least one is.
     * An empty array, and any value that is not an array, is not associative.
     *
     * @param mixed $array the value to check
     * @param bool $allStrings whether every key must be a string
     * @return bool whether the value is an associative array
     */
    public function isAssociative(mixed $array, bool $allStrings = true): bool;

    /**
     * Tells whether the value is an indexed array.
     *
     * An array is indexed when all its keys are integers, and, with `$consecutive` on, when they run from 0 without
     * a gap. An empty array is indexed; any value that is not an array is not.
     *
     * @param mixed $array the value to check
     * @param bool $consecutive whether the keys must be the consecutive sequence from 0
     * @return bool whether the value is an indexed array
     */
    public function isIndexed(mixed $array, bool $consecutive = false): bool;

    /**
     * Tells whether an array or `Traversable` contains the value, as `in_array()` does for an array.
     *
     * @param mixed $needle the value to look for
     * @param mixed $haystack the values to search: an array or a `Traversable`
     * @param bool $strict whether to compare with `===` rather than `==`
     * @return bool whether the value was found
     * @throws \InvalidArgumentException when `$haystack` is neither an array nor a `Traversable`
     */
    public function isIn(mixed $needle, mixed $haystack, bool $strict = false): bool;

    /**
     * Tells whether the value is an array or a `Traversable`.
     *
     * @param mixed $var the value to check
     * @return bool whether the value can be iterated
     * @phpstan-assert-if-true iterable<mixed, mixed> $var
     */
    public function isTraversable(mixed $var): bool;

    /**
     * Tells whether every value of `$needles` is in `$haystack`.
     *
     * @param mixed $needles the values that must all be in `$haystack`: an array or a `Traversable`
     * @param mixed $haystack the values to search: an array or a `Traversable`
     * @param bool $strict whether to compare with `===` rather than `==`
     * @return bool whether `$needles` is a subset of `$haystack`
     * @throws \InvalidArgumentException when `$needles` or `$haystack` is neither an array nor a `Traversable`
     */
    public function isSubset(mixed $needles, mixed $haystack, bool $strict = false): bool;
}
