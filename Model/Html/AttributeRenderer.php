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

use Hryvinskyi\Base\Api\Html\AttributeRendererInterface;
use Hryvinskyi\Base\Api\Html\CssOptionsInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Api\Json\JsonEncoderInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Renders HTML attributes in a configurable preferred order, expanding data attributes and class and style arrays.
 */
class AttributeRenderer implements AttributeRendererInterface
{
    /**
     * @param HtmlEncoderInterface $htmlEncoder encodes attribute values
     * @param JsonEncoderInterface $jsonEncoder writes array values as HTML-safe JSON
     * @param CssOptionsInterface $cssOptions writes a `style` array as style text
     * @param TextConverter $textConverter turns scalar and `Stringable` values into text
     * @param array<string> $attributeOrder attribute names rendered first, in this order; only the values count,
     *     so the keys may be item names
     * @param array<string> $dataAttributes attribute names whose array value is expanded into `name-key`
     *     attributes; matched loosely against the attribute name, so a numeric string also matches its integer
     * @param bool $normalizeClassAttribute whether a `class` array is rendered without duplicate classes; the CSS
     *     options service reads the same switch when it merges classes, so both take one value
     */
    public function __construct(
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly JsonEncoderInterface $jsonEncoder,
        private readonly CssOptionsInterface $cssOptions,
        private readonly TextConverter $textConverter,
        private readonly array $attributeOrder,
        private readonly array $dataAttributes,
        private readonly bool $normalizeClassAttribute
    ) {
    }

    /**
     * @inheritDoc
     */
    public function render(array $attributes): string
    {
        $html = '';
        foreach ($this->sort($attributes) as $name => $value) {
            $html .= $this->renderAttribute($name, $value);
        }

        return $html;
    }

    /**
     * Moves the attributes named in the preferred order to the front, in that order.
     *
     * An attribute whose value is `null` keeps its own place; a single attribute is returned as it is.
     *
     * @param array<mixed> $attributes attribute name => value
     * @return array<mixed> the attributes in rendering order
     */
    private function sort(array $attributes): array
    {
        if (count($attributes) <= 1) {
            return $attributes;
        }

        $sorted = [];
        foreach ($this->attributeOrder as $name) {
            if (isset($attributes[$name])) {
                $sorted[$name] = $attributes[$name];
            }
        }

        return array_merge($sorted, $attributes);
    }

    /**
     * Renders one attribute with its leading space, or an empty string when the value renders nothing.
     *
     * @param int|string $name the attribute name
     * @param mixed $value the attribute value
     * @return string the rendered attribute
     * @throws InvalidParamException when the value has no text form
     */
    private function renderAttribute(int|string $name, mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? " $name" : '';
        }
        if ($value === null) {
            return '';
        }
        if (!is_array($value)) {
            return " $name=\"" . $this->htmlEncoder->encode($this->textConverter->toText($value)) . '"';
        }
        if (in_array($name, $this->dataAttributes)) {
            return $this->renderDataAttributes($name, $value);
        }
        if ($name === 'class') {
            return $this->renderClass($value);
        }
        if ($name === 'style') {
            return $value === []
                ? ''
                : ' style="' . $this->htmlEncoder->encode($this->cssOptions->cssStyleFromArray($value) ?? '') . '"';
        }

        return " $name='" . $this->jsonEncoder->htmlEncode($value) . "'";
    }

    /**
     * Renders a `class` array, dropping empty classes and, when class normalisation is on, duplicates.
     *
     * @param array<mixed> $classes the classes
     * @return string the rendered attribute, or an empty string for an empty array
     * @throws InvalidParamException when a class has no text form
     */
    private function renderClass(array $classes): string
    {
        if ($classes === []) {
            return '';
        }
        if ($this->normalizeClassAttribute && count($classes) > 1) {
            $classes = array_unique(explode(' ', implode(' ', $this->textConverter->texts($classes))));
        }

        return ' class="'
            . $this->htmlEncoder->encode(implode(' ', $this->textConverter->texts(array_filter($classes))))
            . '"';
    }

    /**
     * Renders an expanded attribute such as `data`: one `name-key` attribute per array element.
     *
     * A nested array is rendered as single-quoted HTML-safe JSON, a boolean as a boolean attribute, and `null` is
     * skipped.
     *
     * @param int|string $name the attribute name, e.g. `data`
     * @param array<mixed> $values the attribute value
     * @return string the rendered attributes, each with a leading space
     * @throws InvalidParamException when an element has no text form
     */
    private function renderDataAttributes(int|string $name, array $values): string
    {
        $html = '';
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $html .= " $name-$key='" . $this->jsonEncoder->htmlEncode($value) . "'";
                continue;
            }
            if (is_bool($value)) {
                $html .= $value ? " $name-$key" : '';
                continue;
            }
            if ($value !== null) {
                $html .= " $name-$key=\"" . $this->htmlEncoder->encode($this->textConverter->toText($value)) . '"';
            }
        }

        return $html;
    }
}
