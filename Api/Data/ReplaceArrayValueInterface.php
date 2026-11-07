<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Data;

/**
 * Marks a value that `ArrayMergerInterface::merge()` puts in place of the former one instead of merging recursively.
 *
 * Obtain an empty instance from the generated `ReplaceArrayValueInterfaceFactory` with `create()` and no arguments,
 * then carry the value with `withValue()`:
 *
 * ```php
 * $replacement = $this->replaceArrayValueFactory->create()->withValue(['gii']);
 * ```
 *
 * Do not hand the value to `create()`: in developer mode the object manager rewrites array arguments (an array with
 * an `argument` key is replaced, a nested item with an `instance` key becomes an object), while production mode
 * passes them through, so the same call would carry a different value in each mode. `withValue()` keeps the value
 * out of that resolution.
 *
 * @api
 */
interface ReplaceArrayValueInterface
{
    /**
     * Returns the value that replaces the former one; `null` until a value is set with `withValue()`.
     *
     * @return mixed the replacement value
     */
    public function getValue(): mixed;

    /**
     * Returns a copy carrying the value; this instance is left unchanged.
     *
     * @param mixed $value the replacement value, any data
     * @return ReplaceArrayValueInterface the copy carrying the value
     */
    public function withValue(mixed $value): ReplaceArrayValueInterface;
}
