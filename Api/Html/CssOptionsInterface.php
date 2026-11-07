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

namespace Hryvinskyi\Base\Api\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Edits the `class` and `style` entries of HTML tag options, and converts CSS styles between text and arrays.
 *
 * Every method returns the edited options; the options passed in are never changed.
 *
 * @api
 */
interface CssOptionsInterface
{
    /**
     * Adds one or more CSS classes to the options.
     *
     * A class already present is not added again. When `class` is an array, a class given under a string key is
     * added only if that key is not set yet: an existing named class is never overridden. When class
     * normalisation is on, duplicates already in the options are removed as well. Without a `class` entry, the
     * given value is stored as it is.
     *
     * @param array<mixed> $options the tag options
     * @param string|array<string> $class the class, or the classes, to add
     * @return array<mixed> the options with the classes added
     * @throws InvalidParamException when an existing class has no text form
     */
    public function addCssClass(array $options, string|array $class): array;

    /**
     * Removes one or more CSS classes from the options; the `class` entry is removed when no class is left.
     *
     * An array of classes keeps the keys of the classes that remain.
     *
     * @param array<mixed> $options the tag options
     * @param string|array<string> $class the class, or the classes, to remove
     * @return array<mixed> the options without the classes
     * @throws InvalidParamException when an existing class has no text form
     */
    public function removeCssClass(array $options, string|array $class): array;

    /**
     * Merges a CSS style into the options' `style` entry, which is then stored as text.
     *
     * @param array<mixed> $options the tag options
     * @param string|array<mixed> $style the style as text (`'width: 100px; height: 200px'`) or as
     *     property => value pairs
     * @param bool $overwrite whether a property in the new style replaces the same property in the existing one
     * @return array<mixed> the options with the merged style
     * @throws InvalidParamException when the existing style or a property value has no text form
     */
    public function addCssStyle(array $options, string|array $style, bool $overwrite = true): array;

    /**
     * Removes CSS properties from the options' `style` entry; the entry becomes `null` when no property is left.
     *
     * Options without a non-empty `style` entry are returned unchanged.
     *
     * @param array<mixed> $options the tag options
     * @param string|array<string> $properties the property, or the properties, to remove
     * @return array<mixed> the options without the properties
     * @throws InvalidParamException when the existing style or a property value has no text form
     */
    public function removeCssStyle(array $options, string|array $properties): array;

    /**
     * Converts property => value pairs to style text: `['width' => '100px']` becomes `'width: 100px;'`.
     *
     * @param array<mixed> $style property => value pairs
     * @return string|null the style text, or `null` for an empty array so that no `style` attribute is rendered
     * @throws InvalidParamException when a property value has no text form
     */
    public function cssStyleFromArray(array $style): ?string;

    /**
     * Converts style text to property => value pairs: `'width: 100px;'` becomes `['width' => '100px']`.
     *
     * @param string $style the style text
     * @return array<string, string> property => value pairs, names and values trimmed
     */
    public function cssStyleToArray(string $style): array;
}
