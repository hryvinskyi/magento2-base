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

use Hryvinskyi\Base\Api\Arrays\ArrayPathReaderInterface;
use Hryvinskyi\Base\Api\Arrays\ObjectToArrayConverterInterface;
use Hryvinskyi\Base\Api\Data\ArrayableInterface;
use Traversable;

/**
 * Converts objects, and arrays holding objects, into arrays.
 */
class ObjectToArrayConverter implements ObjectToArrayConverterInterface
{
    /**
     * @param ArrayPathReaderInterface $reader reads a mapped field given as a key path or an anonymous function
     */
    public function __construct(
        private readonly ArrayPathReaderInterface $reader
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toArray(mixed $object, array $properties = [], bool $recursive = true): array
    {
        if (is_array($object)) {
            return $recursive ? $this->convertNested($object, $properties) : $object;
        }

        if ($object instanceof \DateTimeInterface) {
            return (array)$object;
        }

        if (!is_object($object)) {
            return [$object];
        }

        $mapping = $properties[get_class($object)] ?? [];
        $result = $mapping !== [] ? $this->mappedFields($object, $mapping) : $this->ownFields($object, $recursive);

        return $recursive ? $this->convertNested($result, $properties) : $result;
    }

    /**
     * Converts the arrays and objects held by the array, keeping its other values.
     *
     * @param array<array-key,mixed> $array the array whose values are converted
     * @param array<string,array<int|string,string|\Closure>> $properties the fields to export, per class name
     * @return array<array-key,mixed> the array with its arrays and objects converted
     */
    private function convertNested(array $array, array $properties): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $array[$key] = $this->toArray($value, $properties, true);
            }
        }

        return $array;
    }

    /**
     * Exports the fields the mapping names: a listed name as the property, a keyed one through the path reader.
     *
     * Reading a listed property the object does not have raises PHP's own warning for the undefined property.
     *
     * @param object $object the object to export
     * @param array<int|string,string|\Closure> $mapping the fields to export
     * @return array<array-key,mixed> the exported fields
     */
    private function mappedFields(object $object, array $mapping): array
    {
        $result = [];
        foreach ($mapping as $key => $name) {
            if (is_int($key) && is_string($name)) {
                $result[$name] = $object->$name;
                continue;
            }
            $result[$key] = $this->reader->getValue($object, $name);
        }

        return $result;
    }

    /**
     * Exports an object that has no mapping, through its own array form, its iterator or its public properties.
     *
     * @param object $object the object to export
     * @param bool $recursive whether an object exporting itself returns its embedded objects as arrays
     * @return array<array-key,mixed> the exported fields
     */
    private function ownFields(object $object, bool $recursive): array
    {
        if ($object instanceof ArrayableInterface) {
            return $object->toArray([], [], $recursive);
        }

        if ($object instanceof Traversable) {
            return iterator_to_array($object);
        }

        return get_object_vars($object);
    }
}
