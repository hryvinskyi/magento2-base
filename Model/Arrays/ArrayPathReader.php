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

use ArrayAccess;
use Hryvinskyi\Base\Api\Arrays\ArrayPathReaderInterface;

/**
 * Reads values by key, key path or callback from arrays, `ArrayAccess` objects and objects.
 */
class ArrayPathReader implements ArrayPathReaderInterface
{
    /**
     * @param ArrayKeyNormalizer $keys casts a looked-up key the way PHP stores it
     */
    public function __construct(
        private readonly ArrayKeyNormalizer $keys
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getValue(mixed $source, string|int|float|\Closure|array $key, mixed $default = null): mixed
    {
        if ($key instanceof \Closure) {
            return $key($source, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $source = $this->getValue($source, $keyPart);
            }
            $key = $lastKey ?? '';
        }

        $name = (string)$key;
        if (is_object($source) && property_exists($source, $name)) {
            return $source->$name;
        }

        if ((is_array($source) || $source instanceof ArrayAccess) && $this->keyExists($key, $source)) {
            return is_array($source) ? $source[$this->keys->toKey($key)] : $source[$key];
        }

        if ($key && ($pos = strrpos($name, '.')) !== false) {
            $source = $this->getValue($source, substr($name, 0, $pos), $default);
            $key = substr($name, $pos + 1);
            $name = $key;
        }

        if ((is_array($source) || $source instanceof ArrayAccess) && $this->keyExists($key, $source)) {
            return is_array($source) ? $source[$this->keys->toKey($key)] : $source[$key];
        }

        if (is_object($source)) {
            return $this->readProperty($source, $name, $default);
        }

        return $default;
    }

    /**
     * @inheritdoc
     */
    public function keyExists(string|int|float|null $key, array|ArrayAccess $array, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            if (is_array($array) && array_key_exists($this->keys->toKey($key), $array)) {
                return true;
            }
            // array_key_exists() does not work on objects, so an ArrayAccess object is asked through offsetExists()
            return $array instanceof ArrayAccess && $array->offsetExists($key);
        }

        if ($array instanceof ArrayAccess) {
            throw new \InvalidArgumentException(
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
     * Reads a property the object may not declare, leaving a missing one to PHP's own warning or to `__get()`.
     *
     * Whether a property is accessible cannot be told reliably beforehand, so the read is attempted.
     *
     * @param object $source the object to read
     * @param string $name the property name
     * @param mixed $default the value returned when `__get()` throws on an `ArrayAccess` object
     * @return mixed the property value
     * @throws \Exception when `__get()` throws and the object does not implement `ArrayAccess`
     */
    private function readProperty(object $source, string $name, mixed $default): mixed
    {
        try {
            return $this->fetchProperty($source, $name);
        } catch (\Exception $e) {
            if ($source instanceof ArrayAccess) {
                return $default;
            }
            throw $e;
        }
    }

    /**
     * Fetches the property; an object's `__get()` may throw anything out of this call.
     *
     * @param object $source the object to read
     * @param string $name the property name
     * @return mixed the property value
     */
    private function fetchProperty(object $source, string $name): mixed
    {
        return $source->$name;
    }
}
