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
 * Reads a value out of an array, an `ArrayAccess` object or an object by key, key path or callback.
 *
 * @api
 */
interface ArrayPathReaderInterface
{
    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     *
     * If the key does not exist in the array or object, the default value is returned instead.
     *
     * The key may be given in a dot format to retrieve the value of a sub-array or the property of an embedded
     * object: for the key `x.y.z` the value is `$source['x']['y']['z']` or `$source->x->y->z`. If `$source['x']` or
     * `$source->x` is neither an array nor an object, the default value is returned. An element whose literal key
     * is `x.y.z` wins over the path; to never split on dots, give the path as a list of keys
     * (`['x', 'y', 'z']`), whose items are taken literally.
     *
     * Reading a property an object does not have raises PHP's own warning for the undefined property; an
     * exception thrown by the object's `__get()` is rethrown unless the object implements `ArrayAccess`, in which
     * case the default value is returned.
     *
     * ```php
     * $username = $reader->getValue($data, 'username');
     * $street = $reader->getValue($users, 'address.street');
     * $value = $reader->getValue($versions, ['1.0', 'date']);
     * $fullName = $reader->getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * ```
     *
     * @param mixed $source array, `ArrayAccess` or object to extract the value from; any other value yields the
     * default
     * @param string|int|float|\Closure|array<array-key,string|int|float> $key key name of the array element, a list
     * of keys, the property name of the object, or an anonymous function `function($source, $default)` returning the
     * value
     * @param mixed $default the value returned when the key does not exist; not used when an existing object
     * property is read
     * @return mixed the value of the element if found, the default value otherwise
     * @throws \Exception when the object's `__get()` throws and the object does not implement `ArrayAccess`
     */
    public function getValue(mixed $source, string|int|float|\Closure|array $key, mixed $default = null): mixed;

    /**
     * Checks whether the array or `ArrayAccess` object contains the key.
     *
     * Unlike `array_key_exists()`, an `ArrayAccess` object is asked through `offsetExists()`, and the comparison can
     * ignore case. A key holding `null` exists.
     *
     * @param string|int|float|null $key the key to check
     * @param array<array-key,mixed>|\ArrayAccess<mixed,mixed> $array the array with keys to check
     * @param bool $caseSensitive whether the key comparison is case-sensitive
     * @return bool whether the array contains the key
     * @throws \InvalidArgumentException when `$array` is an `ArrayAccess` object in case-insensitive mode
     */
    public function keyExists(string|int|float|null $key, array|\ArrayAccess $array, bool $caseSensitive = true): bool;
}
