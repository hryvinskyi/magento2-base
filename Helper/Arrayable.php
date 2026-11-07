<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * An object that can describe and export its own fields as an array.
 */
interface Arrayable
{
    /**
     * Returns the list of fields that `toArray()` returns by default, when no specific fields are requested.
     *
     * A field is a named element in the array returned by `toArray()`.
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function ($model) {
     *         return $model->first_name . ' ' . $model->last_name;
     *     },
     * ];
     * ```
     *
     * @return array<int|string,string|\Closure> the list of field names or field definitions.
     * @see toArray()
     */
    public function fields();

    /**
     * Returns the additional fields that `toArray()` can return besides those listed in `fields()`.
     *
     * This method is similar to `fields()` except that the list of fields declared
     * by this method are not returned by default by `toArray()`. Only when a field in the list
     * is explicitly requested, will it be included in the result of `toArray()`.
     *
     * @return array<int|string,string|\Closure> the list of expandable field names or field definitions.
     * Please refer to `fields()` on the format of the return value.
     * @see toArray()
     * @see fields()
     */
    public function extraFields();

    /**
     * Converts the object into an array.
     *
     * @param array<array-key,string> $fields the fields that the output array should contain. Fields not
     * specified in `fields()` will be ignored. If this parameter is empty, all fields as specified in `fields()`
     * will be returned.
     * @param array<array-key,string> $expand the additional fields that the output array should contain.
     * Fields not specified in `extraFields()` will be ignored. If this parameter is empty, no extra fields
     * will be returned.
     * @param bool $recursive whether to recursively return array representation of embedded objects.
     * @return array<array-key,mixed> the array representation of the object
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true);
}
