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

use Hryvinskyi\Base\Api\Html\CssOptionsInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Edits the `class` and `style` entries of tag options and returns the edited copy.
 */
class CssOptions implements CssOptionsInterface
{
    /**
     * @param TextConverter $textConverter turns classes and style values into text
     * @param bool $normalizeClassAttribute whether merging classes also removes duplicates already present; the
     *     attribute renderer reads the same switch when it renders a `class` array, so both take one value
     */
    public function __construct(
        private readonly TextConverter $textConverter,
        private readonly bool $normalizeClassAttribute
    ) {
    }

    /**
     * @inheritDoc
     */
    public function addCssClass(array $options, string|array $class): array
    {
        if (!isset($options['class'])) {
            $options['class'] = $class;

            return $options;
        }

        if (is_array($options['class'])) {
            $options['class'] = $this->mergeCssClasses($options['class'], (array)$class);

            return $options;
        }

        $options['class'] = implode(
            ' ',
            $this->textConverter->texts(
                $this->mergeCssClasses($this->splitClasses($options['class']), (array)$class)
            )
        );

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function removeCssClass(array $options, string|array $class): array
    {
        if (!isset($options['class'])) {
            return $options;
        }

        if (is_array($options['class'])) {
            $classes = $this->diffByText($options['class'], (array)$class);
            if ($classes === []) {
                unset($options['class']);
            } else {
                $options['class'] = $classes;
            }

            return $options;
        }

        $classes = array_diff($this->splitClasses($options['class']), (array)$class);
        if ($classes === []) {
            unset($options['class']);
        } else {
            $options['class'] = implode(' ', $classes);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function addCssStyle(array $options, string|array $style, bool $overwrite = true): array
    {
        if (!empty($options['style'])) {
            $oldStyle = $this->styleAsArray($options['style']);
            $newStyle = is_array($style) ? $style : $this->cssStyleToArray($style);
            if (!$overwrite) {
                foreach (array_keys($newStyle) as $property) {
                    if (isset($oldStyle[$property])) {
                        unset($newStyle[$property]);
                    }
                }
            }
            $style = array_merge($oldStyle, $newStyle);
        }
        $options['style'] = is_array($style) ? $this->cssStyleFromArray($style) : $style;

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function removeCssStyle(array $options, string|array $properties): array
    {
        if (empty($options['style'])) {
            return $options;
        }

        $style = $this->styleAsArray($options['style']);
        foreach ((array)$properties as $property) {
            unset($style[$property]);
        }
        $options['style'] = $this->cssStyleFromArray($style);

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function cssStyleFromArray(array $style): ?string
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= $name . ': ' . $this->textConverter->toText($value) . '; ';
        }

        return $result === '' ? null : rtrim($result);
    }

    /**
     * @inheritDoc
     */
    public function cssStyleToArray(string $style): array
    {
        $result = [];
        foreach (explode(';', $style) as $property) {
            $property = explode(':', $property);
            if (count($property) > 1) {
                $result[trim($property[0])] = trim($property[1]);
            }
        }

        return $result;
    }

    /**
     * Returns an existing `style` entry as property => value pairs, parsing it when it is text.
     *
     * @param mixed $style the `style` entry of the options
     * @return array<mixed> property => value pairs
     * @throws InvalidParamException when the entry is neither an array nor has a text form
     */
    private function styleAsArray(mixed $style): array
    {
        return is_array($style) ? $style : $this->cssStyleToArray($this->textConverter->toText($style));
    }

    /**
     * Splits a `class` entry held as text into its classes, dropping empty parts.
     *
     * @param mixed $classes the `class` entry of the options
     * @return list<string> the classes
     * @throws InvalidParamException when the entry has no text form
     */
    private function splitClasses(mixed $classes): array
    {
        return preg_split('/\s+/', $this->textConverter->toText($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Merges classes into existing ones; an existing class under a string key is never overridden.
     *
     * @param array<mixed> $existingClasses the classes already in the options
     * @param array<string> $additionalClasses the classes to add
     * @return array<mixed> the merged classes
     * @throws InvalidParamException when a class has no text form
     */
    private function mergeCssClasses(array $existingClasses, array $additionalClasses): array
    {
        foreach ($additionalClasses as $key => $class) {
            if (is_int($key) && !in_array($class, $existingClasses)) {
                $existingClasses[] = $class;
            } elseif (!isset($existingClasses[$key])) {
                $existingClasses[$key] = $class;
            }
        }

        return $this->normalizeClassAttribute ? $this->uniqueByText($existingClasses) : $existingClasses;
    }

    /**
     * Removes the values whose text repeats an earlier one, keeping keys and values, as `array_unique()` does.
     *
     * @param array<mixed> $values the values to filter
     * @return array<mixed> the first value of each text, under its key
     * @throws InvalidParamException when a value has no text form
     */
    private function uniqueByText(array $values): array
    {
        $seen = [];
        $unique = [];
        foreach ($values as $key => $value) {
            $text = $this->textConverter->toText($value);
            if (!isset($seen[$text])) {
                $seen[$text] = true;
                $unique[$key] = $value;
            }
        }

        return $unique;
    }

    /**
     * Removes the values whose text is one of the given texts, keeping keys and values, as `array_diff()` does.
     *
     * @param array<mixed> $values the values to filter
     * @param array<string> $remove the texts to remove
     * @return array<mixed> the remaining values, under their keys
     * @throws InvalidParamException when a value has no text form
     */
    private function diffByText(array $values, array $remove): array
    {
        $removed = array_fill_keys($remove, true);
        $kept = [];
        foreach ($values as $key => $value) {
            if (!isset($removed[$this->textConverter->toText($value)])) {
                $kept[$key] = $value;
            }
        }

        return $kept;
    }
}
