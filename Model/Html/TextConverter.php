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

namespace Hryvinskyi\Base\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Converts values to the text PHP would print for them, refusing values that have no text form.
 *
 * Internal to the HTML services of this module, which share it so an attribute value, a class and a style property
 * become text the same way.
 */
class TextConverter
{
    /**
     * Converts a value to text.
     *
     * A string stays as it is, a scalar or `null` goes through a string cast, a `Stringable` object through
     * `__toString()`.
     *
     * @param mixed $value the value to convert
     * @return string the text
     * @throws InvalidParamException when the value is an array, a resource or an object without `__toString()`
     */
    public function toText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }

        throw new InvalidParamException(
            sprintf('A value of type %s cannot be rendered as text.', get_debug_type($value))
        );
    }

    /**
     * Converts every value of an array to text with toText(), keeping the keys.
     *
     * @param array<mixed> $values the values to convert
     * @return array<string> the texts, under the keys of their values
     * @throws InvalidParamException when a value has no text form
     */
    public function texts(array $values): array
    {
        $texts = [];
        foreach ($values as $key => $value) {
            $texts[$key] = $this->toText($value);
        }

        return $texts;
    }
}
