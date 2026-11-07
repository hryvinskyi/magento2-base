<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * @copyright Copyright (c) 2019 Volodymyr Hryvinskyi. All rights reserved.
 * @author Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 * @github <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

use ArrayAccess;
use InvalidArgumentException;
use Traversable;

/**
 * Static helpers for reading, building, sorting and filtering arrays and arrays of objects.
 */
class ArrayHelper
{
    /**
     * Converts an object or an array of objects into an array.
     *
     * The properties specified for each class are a list of the following format:
     *
     * ```php
     * [
     *     Post::class => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     *
     * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
     *
     * ```php
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ```
     *
     * An object without a property mapping is converted through `Arrayable::toArray()` when it implements
     * `Arrayable`, through its iterator when it is `Traversable`, and through its public properties otherwise.
     * Any other value is returned wrapped in a one-element array.
     *
     * @param mixed $object the value to be converted into an array
     * @param array<string,array<int|string,string|\Closure>> $properties a mapping from object class names
     * to the properties that need to be put into the resulting arrays
     * @param bool $recursive whether to recursively convert properties which are objects into arrays
     * @return array<array-key,mixed> the array representation of the object
     */
    public static function toArray($object, $properties = [], $recursive = true)
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = static::toArray($value, $properties, true);
                    }
                }
            }

            return $object;
        }

        if ($object instanceof \DateTimeInterface) {
            return (array)$object;
        }

        if (!is_object($object)) {
            return [$object];
        }

        $className = get_class($object);
        if (!empty($properties) && !empty($properties[$className])) {
            $result = [];
            foreach ($properties[$className] as $key => $name) {
                if (is_int($key) && is_string($name)) {
                    $result[$name] = $object->$name;
                } else {
                    $result[$key] = static::getValue($object, $name);
                }
            }

            return $recursive ? static::toArray($result, $properties) : $result;
        }

        if ($object instanceof Arrayable) {
            $result = $object->toArray([], [], $recursive);
        } elseif ($object instanceof Traversable) {
            $result = iterator_to_array($object);
        } else {
            $result = get_object_vars($object);
        }

        return $recursive ? static::toArray($result, $properties) : $result;
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * You can use an `UnsetArrayValue` object to unset value from previous array or
     * `ReplaceArrayValue` to force replace former value instead of recursive merging.
     * Further arrays may be passed as third, fourth, ... arguments. A Traversable is merged entry by entry and any
     * other object by its public properties; an argument that is neither an array nor an object is skipped.
     *
     * @param array<array-key,mixed> $a array to be merged to
     * @param array<array-key,mixed>|object $b array to be merged from
     * @return array<array-key,mixed> the merged array (the original arrays are not changed.)
     * @throws InvalidParamException When a Traversable yields a key that is neither an integer nor a string.
     */
    public static function merge($a, $b)
    {
        $res = $a;
        foreach (array_slice(func_get_args(), 1) as $next) {
            if (is_object($next) && !$next instanceof \Traversable) {
                $next = get_object_vars($next);
            }
            if (!is_iterable($next)) {
                continue;
            }
            foreach ($next as $k => $v) {
                if (!is_int($k) && !is_string($k)) {
                    throw new InvalidParamException('Only integer and string keys can be merged.');
                }
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     *
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Reading a property an object does not have raises PHP's own warning for the undefined property;
     * an exception thrown by the object's `__get()` is rethrown unless the object implements `ArrayAccess`,
     * in which case the default value is returned.
     *
     * Below are some usage examples,
     *
     * ```php
     * // working with array
     * $username = \Hryvinskyi\Base\Helper\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \Hryvinskyi\Base\Helper\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \Hryvinskyi\Base\Helper\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \Hryvinskyi\Base\Helper\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \Hryvinskyi\Base\Helper\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param mixed $array array or object to extract value from; any other value yields the default
     * @param string|int|float|\Closure|array<array-key,string|int|float> $key key name of the array element,
     * an array of keys or property name of the object, or an anonymous function returning the value.
     * The anonymous function signature should be: `function($array, $defaultValue)`.
     * @param mixed $default the default value to be returned if the specified array key does not exist.
     * Not used when getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     * @throws \Exception when the object's `__get()` throws and the object does not implement `ArrayAccess`
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        $name = (string)$key;
        if (is_object($array) && property_exists($array, $name)) {
            return $array->$name;
        }

        if ((is_array($array) || $array instanceof ArrayAccess) && static::keyExists($key, $array)) {
            return is_array($array) ? $array[self::toArrayKey($key)] : $array[$key];
        }

        if ($key && ($pos = strrpos($name, '.')) !== false) {
            $array = static::getValue($array, substr($name, 0, $pos), $default);
            $key = substr($name, $pos + 1);
            $name = $key;
        }

        if ((is_array($array) || $array instanceof ArrayAccess) && static::keyExists($key, $array)) {
            return is_array($array) ? $array[self::toArrayKey($key)] : $array[$key];
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            try {
                return $array->$name;
            } catch (\Exception $e) {
                if ($array instanceof ArrayAccess) {
                    return $default;
                }
                throw $e;
            }
        }

        return $default;
    }

    /**
     * Writes a value into an associative array at the key path specified.
     *
     * If there is no such key path yet, it will be created recursively; an existing value on the path
     * that is not an array is wrapped into an array. If the key exists, it will be overwritten.
     * A `null` path replaces the whole array with the value.
     *
     * ```php
     * $array = ['key' => ['in' => ['val1', 'key' => 'val']]];
     * ArrayHelper::setValue($array, 'key.in.0', ['arr' => 'val']);
     * // $array = ['key' => ['in' => [['arr' => 'val'], 'key' => 'val']]];
     *
     * ArrayHelper::setValue($array, ['key', 'in.array', 'ok.schema'], 'array');
     * // a list path never splits on dots
     * ```
     *
     * @template TValue
     * @param array<array-key,mixed> $array the array to write the value to
     * @param string|int|array<array-key,string|int>|null $path the path of where do you want to write a value
     * to `$array`: a dot-separated key, a list of keys, or `null` to replace `$array` entirely
     * @param TValue $value the value to be written
     * @param-out ($path is null ? TValue : array<array-key, mixed>) $array
     * @return void
     */
    public static function setValue(&$array, $path, $value)
    {
        if ($path === null) {
            $array = $value;
            return;
        }

        $keys = is_array($path) ? $path : explode('.', (string)$path);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key])) {
                $array[$key] = [];
            }
            if (!is_array($array[$key])) {
                $array[$key] = [$array[$key]];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Removes an item from an array and returns the value.
     *
     * If the key does not exist in the array, or `$array` is not an array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = \Hryvinskyi\Base\Helper\ArrayHelper::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param array<TKey,TValue>|null $array the array to extract value from
     * @param-out ($array is array ? array<TKey, TValue> : null) $array
     * @param int|string $key key name of the array element
     * @param TDefault $default the default value to be returned if the specified key does not exist
     * @return TValue|TDefault the value of the element if found, default value otherwise
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * Removes items with matching values from the array and returns the removed items.
     *
     * Example,
     *
     * ```php
     * $array = ['Bob' => 'Dylan', 'Michael' => 'Jackson', 'Mick' => 'Jagger', 'Janet' => 'Jackson'];
     * $removed = \Hryvinskyi\Base\Helper\ArrayHelper::removeValue($array, 'Jackson');
     * // result:
     * // $array = ['Bob' => 'Dylan', 'Mick' => 'Jagger'];
     * // $removed = ['Michael' => 'Jackson', 'Janet' => 'Jackson'];
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey,TValue>|null $array the array where to look the value from; any other value is left as is
     * @param-out ($array is array ? array<TKey, TValue> : null) $array
     * @param mixed $value the value to remove from the array, compared strictly
     * @return array<TKey,TValue> the items that were removed from the array
     */
    public static function removeValue(&$array, $value)
    {
        $result = [];
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($val === $value) {
                    $result[$key] = $val;
                    unset($array[$key]);
                }
            }
        }

        return $result;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     *
     * The input should be either multidimensional array or an array of objects.
     *
     * The $key can be either a key name of the sub-array, a property name of object, or an anonymous
     * function that must return the value that will be used as a key.
     *
     * $groups is an array of keys, that will be used to group the input array into one or more sub-arrays based
     * on keys specified.
     *
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     *
     * An anonymous function can be used in the grouping array as well.
     *
     * ```php
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * Passing `id` as a third argument will group `$array` by `id`:
     *
     * ```php
     * $result = ArrayHelper::index($array, null, 'id');
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * The anonymous function can be used in the array of grouping keys as well:
     *
     * ```php
     * $result = ArrayHelper::index($array, 'data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param iterable<mixed> $array the array that needs to be indexed or grouped
     * @param string|int|\Closure|null $key the column name or anonymous function which result will be used
     * to index the array
     * @param string|int|array<array-key,string|int|\Closure>|null $groups the array of keys, that will be used
     * to group the input array by one or more keys. If the $key attribute or its value for the particular element
     * is null and $groups is not defined, the array element will be discarded. Otherwise, if $groups is specified,
     * array element will be added to the result array without any key.
     * @return array<array-key,mixed> the indexed and/or grouped array
     * @throws \TypeError when a key or group value is neither a scalar nor `null`
     */
    public static function index($array, $key, $groups = [])
    {
        $result = [];
        $groups = (array)$groups;

        foreach ($array as $element) {
            $path = [];
            foreach ($groups as $group) {
                $path[] = self::toArrayKey(static::getValue($element, $group));
            }
            $lastArray = &self::groupNode($result, $path);

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::getValue($element, $key);
                if ($value !== null) {
                    if (is_float($value)) {
                        $value = (string) $value;
                    }
                    $lastArray[self::toArrayKey($value)] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * Returns the values of a specified column in an array.
     *
     * The input array should be multidimensional or an array of objects.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param iterable<array-key,mixed> $array the rows to read the column from
     * @param string|int|\Closure $name the column name, property name or anonymous function returning the value
     * @param bool $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     * @return array<array-key,mixed> the list of column values
     */
    public static function getColumn($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     *
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param array<array-key,mixed> $array the rows to build the map from
     * @param string|\Closure $from the key or property name, or anonymous function, giving each map key
     * @param string|\Closure $to the key or property name, or anonymous function, giving each map value
     * @param string|\Closure|null $group the key or property name, or anonymous function, to group the map by
     * @return array<array-key,mixed> the map, grouped when `$group` is given
     * @throws \TypeError when a key or group value is neither a scalar nor `null`
     */
    public static function map($array, $from, $to, $group = null)
    {
        if (is_string($from) && is_string($to) && $group === null
            && strpos($from, '.') === false && strpos($to, '.') === false
        ) {
            return array_column($array, $to, $from);
        }
        $result = [];
        foreach ($array as $element) {
            $key = static::getValue($element, $from);
            $value = static::getValue($element, $to);
            if ($group !== null) {
                $groupKey = self::toArrayKey(static::getValue($element, $group));
                if (!isset($result[$groupKey]) || !is_array($result[$groupKey])) {
                    $result[$groupKey] = [];
                }
                $result[$groupKey][self::toArrayKey($key)] = $value;
            } else {
                $result[self::toArrayKey($key)] = $value;
            }
        }

        return $result;
    }

    /**
     * Checks if the given array contains the specified key.
     *
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison and objects implementing `ArrayAccess`.
     *
     * @param string|int|float|null $key the key to check
     * @param array<array-key,mixed>|ArrayAccess<mixed,mixed> $array the array with keys to check
     * @param bool $caseSensitive whether the key comparison should be case-sensitive
     * @return bool whether the array contains the specified key
     * @throws InvalidArgumentException when `$array` is an `ArrayAccess` object in case-insensitive mode
     */
    public static function keyExists($key, $array, $caseSensitive = true)
    {
        if ($caseSensitive) {
            if (is_array($array) && array_key_exists(self::toArrayKey($key), $array)) {
                return true;
            }
            // `array_key_exists()` does not work on objects, so an ArrayAccess object is asked through offsetExists()
            return $array instanceof ArrayAccess && $array->offsetExists($key);
        }

        if ($array instanceof ArrayAccess) {
            throw new InvalidArgumentException(
                'Second parameter($array) cannot be ArrayAccess in case insensitive mode'
            );
        }

        foreach (array_keys($array) as $k) {
            if (strcasecmp((string)$key, (string)$k) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     *
     * Elements whose sort keys compare equal keep their original order.
     *
     * @template TValue
     * @param array<array-key,TValue> $array the array to be sorted. The array will be modified after calling
     * this method.
     * @param-out array<array-key, TValue> $array
     * @param string|int|\Closure|array<array-key,string|int|\Closure> $key the key(s) to be sorted by. This refers
     * to a key name of the sub-array elements, a property name of the objects, or an anonymous function returning
     * the values for comparison purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param int|array<array-key,int> $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param int|array<array-key,int> $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](http://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @return void
     * @throws InvalidArgumentException if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public static function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return;
        }
        $n = count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new InvalidArgumentException('The length of $direction parameter must be the same as that of $keys.');
        }
        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new InvalidArgumentException('The length of $sortFlag parameter must be the same as that of $keys.');
        }
        $args = [];
        foreach ($keys as $i => $k) {
            $flag = $sortFlag[$i];
            $args[] = static::getColumn($array, $k);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        $args[] = &$array;
        $firstColumn = array_shift($args);
        if (!is_array($firstColumn)) {
            // unreachable: $keys is not empty, so the first argument is always the column of the first key
            return;
        }
        array_multisort($firstColumn, ...$args);
    }

    /**
     * Encodes special characters in an array of strings into HTML entities.
     *
     * Only array values will be encoded by default.
     * If a value is an array, this method will also encode it recursively.
     * Only string values will be encoded.
     *
     * @param array<array-key,mixed> $data data to be encoded
     * @param bool $valuesOnly whether to encode array values only. If false,
     * both the array keys and array values will be encoded.
     * @param string $charset the charset that the data is using, UTF-8 by default
     * @return array<array-key,mixed> the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function htmlEncode($data, $valuesOnly = true, $charset = 'UTF-8')
    {
        $d = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            }
            if (is_string($value)) {
                $d[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            } elseif (is_array($value)) {
                $d[$key] = static::htmlEncode($value, $valuesOnly, $charset);
            } else {
                $d[$key] = $value;
            }
        }

        return $d;
    }

    /**
     * Decodes HTML entities into the corresponding characters in an array of strings.
     *
     * Only array values will be decoded by default.
     * If a value is an array, this method will also decode it recursively.
     * Only string values will be decoded.
     *
     * @param array<array-key,mixed> $data data to be decoded
     * @param bool $valuesOnly whether to decode array values only. If false,
     * both the array keys and array values will be decoded.
     * @return array<array-key,mixed> the decoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function htmlDecode($data, $valuesOnly = true)
    {
        $d = [];
        foreach ($data as $key => $value) {
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars_decode($key, ENT_QUOTES | ENT_SUBSTITUTE);
            }
            if (is_string($value)) {
                $d[$key] = htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE);
            } elseif (is_array($value)) {
                $d[$key] = static::htmlDecode($value, $valuesOnly);
            } else {
                $d[$key] = $value;
            }
        }

        return $d;
    }

    /**
     * Returns a value indicating whether the given value is an associative array.
     *
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     *
     * Note that an empty array, and any value that is not an array, will NOT be considered associative.
     *
     * @param mixed $array the value being checked
     * @param bool $allStrings whether the array keys must be all strings in order for
     * the array to be treated as associative.
     * @return bool whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (empty($array) || !is_array($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a value indicating whether the given value is an indexed array.
     *
     * An array is indexed if all its keys are integers. If `$consecutive` is true,
     * then the array keys must be a consecutive sequence starting from 0.
     *
     * Note that an empty array will be considered indexed, and any value that is not an array will not.
     *
     * @param mixed $array the value being checked
     * @param bool $consecutive whether the array keys must be a consecutive sequence
     * in order for the array to be treated as indexed.
     * @return bool whether the array is indexed
     */
    public static function isIndexed($array, $consecutive = false)
    {
        if (!is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        $keys = array_keys($array);

        if ($consecutive) {
            return $keys === array_keys($keys);
        }

        foreach ($keys as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether an array or `\Traversable` contains an element.
     *
     * This method does the same as the PHP function [in_array()](http://php.net/manual/en/function.in-array.php)
     * but additionally works for objects that implement the `\Traversable` interface.
     *
     * @param mixed $needle The value to look for.
     * @param mixed $haystack The set of values to search: an array or a `\Traversable`.
     * @param bool $strict Whether to enable strict (`===`) comparison.
     * @return bool `true` if `$needle` was found in `$haystack`, `false` otherwise.
     * @throws InvalidArgumentException if `$haystack` is neither traversable nor an array.
     * @see http://php.net/manual/en/function.in-array.php
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if (!static::isTraversable($haystack)) {
            throw new InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        if (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }

        foreach ($haystack as $value) {
            if ($strict ? $needle === $value : $needle == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a variable is an array or `\Traversable`.
     *
     * This method does the same as the PHP function [is_array()](http://php.net/manual/en/function.is-array.php)
     * but additionally works on objects that implement the `\Traversable` interface.
     *
     * @param mixed $var The variable being evaluated.
     * @return bool whether $var is array-like
     * @phpstan-assert-if-true iterable<mixed, mixed> $var
     * @see http://php.net/manual/en/function.is_array.php
     */
    public static function isTraversable($var)
    {
        return is_array($var) || $var instanceof Traversable;
    }

    /**
     * Checks whether an array or `\Traversable` is a subset of another array or `\Traversable`.
     *
     * This method will return `true`, if all elements of `$needles` are contained in
     * `$haystack`. If at least one element is missing, `false` will be returned.
     *
     * @param mixed $needles The values that must **all** be in `$haystack`: an array or a `\Traversable`.
     * @param mixed $haystack The set of value to search: an array or a `\Traversable`.
     * @param bool $strict Whether to enable strict (`===`) comparison.
     * @return bool `true` if `$needles` is a subset of `$haystack`, `false` otherwise.
     * @throws InvalidArgumentException if `$haystack` or `$needles` is neither traversable nor an array.
     */
    public static function isSubset($needles, $haystack, $strict = false)
    {
        if (!static::isTraversable($needles)) {
            throw new InvalidArgumentException('Argument $needles must be an array or implement Traversable');
        }

        foreach ($needles as $needle) {
            if (!static::isIn($needle, $haystack, $strict)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filters array according to rules specified.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     'A' => [1, 2],
     *     'B' => [
     *         'C' => 1,
     *         'D' => 2,
     *     ],
     *     'E' => 1,
     * ];
     *
     * $result = \Hryvinskyi\Base\Helper\ArrayHelper::filter($array, ['A']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * // ]
     *
     * $result = \Hryvinskyi\Base\Helper\ArrayHelper::filter($array, ['A', 'B.C']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * //     'B' => ['C' => 1],
     * // ]
     *
     * $result = \Hryvinskyi\Base\Helper\ArrayHelper::filter($array, ['B', '!B.C']);
     * // $result will be:
     * // [
     * //     'B' => ['D' => 2],
     * // ]
     * ```
     *
     * A rule whose path runs through a value that is not an array matches nothing.
     *
     * @param array<array-key,mixed> $array Source array
     * @param array<array-key,mixed> $filters Rules that define array keys which should be left or removed from
     * results; entries that are neither strings nor integers are ignored. Each rule is:
     * - `var` - `$array['var']` will be left in result.
     * - `var.key` = only `$array['var']['key']` will be left in result.
     * - `!var.key` = `$array['var']['key']` will be removed from result.
     * @return array<array-key,mixed> Filtered array
     */
    public static function filter($array, $filters)
    {
        $result = [];
        $excludeFilters = [];

        foreach ($filters as $filter) {
            if (!is_string($filter) && !is_int($filter)) {
                continue;
            }

            if (is_string($filter) && strncmp($filter, '!', 1) === 0) {
                $excludeFilters[] = substr($filter, 1);
                continue;
            }

            $nodeValue = $array; //set $array as root node
            $keys = explode('.', (string) $filter);
            foreach ($keys as $key) {
                if (!is_array($nodeValue) || !array_key_exists($key, $nodeValue)) {
                    continue 2; //Jump to next filter
                }
                $nodeValue = $nodeValue[$key];
            }

            //We've found a value now let's insert it
            $resultNode = &self::groupNode($result, $keys);
            $resultNode = $nodeValue;
            unset($resultNode);
        }

        foreach ($excludeFilters as $filter) {
            self::removePath($result, explode('.', $filter));
        }

        return $result;
    }

    /**
     * Converts a value to the array key PHP would use for it, refusing values that cannot be a key.
     *
     * @param mixed $value the value to use as an array key
     * @return int|string the array key
     * @throws \TypeError when the value is neither a scalar nor `null`
     */
    private static function toArrayKey(mixed $value): int|string
    {
        if (is_int($value) || is_string($value)) {
            return $value;
        }
        if ($value === null) {
            return '';
        }
        if (is_bool($value) || is_float($value)) {
            return (int)$value;
        }

        throw new \TypeError(sprintf('Cannot access offset of type %s on array', get_debug_type($value)));
    }

    /**
     * Returns a reference to the nested array at the key path, creating the arrays missing on the way.
     *
     * A value on the path that is not an array is replaced by an empty array.
     *
     * @param array<array-key,mixed> $node the array to descend into
     * @param list<int|string> $path the keys to descend through
     * @return array<array-key,mixed> the nested array, by reference
     */
    private static function &groupNode(array &$node, array $path): array
    {
        if ($path === []) {
            return $node;
        }

        $segment = array_shift($path);
        if (!isset($node[$segment]) || !is_array($node[$segment])) {
            $node[$segment] = [];
        }

        return self::groupNode($node[$segment], $path);
    }

    /**
     * Removes the element at the key path, when the whole path exists through arrays.
     *
     * @param array<array-key,mixed> $node the array to remove the element from
     * @param list<string> $path the keys leading to the element
     * @return void
     */
    private static function removePath(array &$node, array $path): void
    {
        $key = array_shift($path);
        if ($key === null || !array_key_exists($key, $node)) {
            return;
        }

        if ($path === []) {
            unset($node[$key]);
            return;
        }

        if (is_array($node[$key])) {
            self::removePath($node[$key], $path);
        }
    }
}
