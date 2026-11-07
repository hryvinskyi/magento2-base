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
 * Builds a changed copy of an array: a value written at a key path, a key removed, the items holding a value removed.
 *
 * No method changes the array it is given; each returns the new array. Where a former by-reference method also
 * returned what it removed, that value is read before the removal, as each method's description says.
 *
 * @api
 */
interface ArrayPathWriterInterface
{
    /**
     * Returns the array with the value written at the key path.
     *
     * Missing arrays on the path are created; an existing value on the path that is not an array (or is `null`) is
     * wrapped into an array (`null` becomes an empty one). An existing value at the end of the path is overwritten.
     *
     * ```php
     * $array = ['key' => ['in' => ['val1', 'key' => 'val']]];
     * $writer->setValue($array, 'key.in.0', ['arr' => 'val']);
     * // ['key' => ['in' => [['arr' => 'val'], 'key' => 'val']]]
     *
     * $writer->setValue($array, ['key', 'in.array', 'ok.schema'], 'array');
     * // a list path never splits on dots
     * ```
     *
     * There is no `null` path: a caller that replaced the whole array with a value uses the value itself.
     *
     * @param array<array-key,mixed> $array the array to write the value into
     * @param string|int|array<array-key,string|int> $path a dot-separated key, or a list of keys taken literally
     * @param mixed $value the value to write
     * @return array<array-key,mixed> the array with the value written
     */
    public function setValue(array $array, string|int|array $path, mixed $value): array;

    /**
     * Returns the array without the key.
     *
     * The key is literal: a dot never descends into a sub-array, and a missing key leaves the array as it is.
     * The value being removed is read beforehand with a literal-key read,
     * `array_key_exists($key, $array) ? $array[$key] : $default` — a key holding `null` yields `null`, not the
     * default. `ArrayPathReaderInterface::getValue()` is not that read: when the literal key is absent it falls
     * through to a dot path, so `getValue(['a' => ['b' => 1]], 'a.b', 'x')` is `1` where the removed value is `'x'`.
     *
     * @param array<array-key,mixed> $array the array to remove the key from
     * @param int|string $key the key of the element to remove
     * @return array<array-key,mixed> the array without the key
     */
    public function remove(array $array, int|string $key): array;

    /**
     * Returns the array without every item identical (`===`) to the value; the remaining items keep their keys.
     *
     * The items being removed are read beforehand with `valuesIdenticalTo()`.
     *
     * ```php
     * $array = ['Bob' => 'Dylan', 'Michael' => 'Jackson', 'Mick' => 'Jagger', 'Janet' => 'Jackson'];
     * $writer->removeValue($array, 'Jackson');       // ['Bob' => 'Dylan', 'Mick' => 'Jagger']
     * $writer->valuesIdenticalTo($array, 'Jackson'); // ['Michael' => 'Jackson', 'Janet' => 'Jackson']
     * ```
     *
     * @param array<array-key,mixed> $array the array to remove the items from
     * @param mixed $value the value to remove, compared strictly
     * @return array<array-key,mixed> the array without the matching items
     */
    public function removeValue(array $array, mixed $value): array;

    /**
     * Returns the items identical (`===`) to the value, with their keys: exactly what `removeValue()` drops.
     *
     * @param array<array-key,mixed> $array the array to look in
     * @param mixed $value the value to look for, compared strictly
     * @return array<array-key,mixed> the matching items
     */
    public function valuesIdenticalTo(array $array, mixed $value): array;
}
