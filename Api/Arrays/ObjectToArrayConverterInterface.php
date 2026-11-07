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
 * Converts an object, or an array of objects, into an array.
 *
 * @api
 */
interface ObjectToArrayConverterInterface
{
    /**
     * Converts an object, or an array of objects, into an array.
     *
     * `$properties` maps class names to the fields to export for objects of exactly that class:
     *
     * ```php
     * [
     *     Post::class => [
     *         'id',
     *         'title',
     *         'createTime' => 'created_at',              // result key => property name or key path
     *         'length' => function ($post) {             // result key => anonymous function
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     *
     * An object without a mapping is converted through `ArrayableInterface::toArray()` when it implements
     * `Hryvinskyi\Base\Api\Data\ArrayableInterface`, through its iterator when it is `Traversable`, as PHP's array
     * cast when it is a `DateTimeInterface`, and through its public properties otherwise. An array is returned with
     * its objects converted; any other value is returned wrapped in a one-element array.
     *
     * @param mixed $object the value to convert
     * @param array<string,array<int|string,string|\Closure>> $properties the fields to export, per class name
     * @param bool $recursive whether objects and arrays inside the result are converted as well
     * @return array<array-key,mixed> the array representation of the value
     */
    public function toArray(mixed $object, array $properties = [], bool $recursive = true): array;
}
