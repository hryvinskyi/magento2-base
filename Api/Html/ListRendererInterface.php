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
 * Renders unordered and ordered lists.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * @api
 */
interface ListRendererInterface
{
    /**
     * Renders an unordered list; with no items, an empty list tag.
     *
     * The options are the attributes of the list tag, except the following:
     *
     * - `tag`: the tag name of the list, `ul` when absent; `false` or `null` renders no list tag.
     * - `encode`: whether the items are HTML-encoded, `true` when absent; ignored when `item` is set.
     * - `separator`: the HTML between the items and around them, a newline when absent.
     * - `itemOptions`: the attributes of the `li` tags; ignored when `item` is set.
     * - `item`: a callback that renders a whole list item. `$index` is the key of the item in `$items`:
     *   `callable(mixed $item, int|string $index): mixed`.
     *
     * @param iterable<mixed> $items the list items
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered list
     * @throws InvalidParamException when the `item` option is not callable, or a value has no text form
     */
    public function ul(iterable $items, array $options = []): string;

    /**
     * Renders an ordered list; with no items, an empty list tag.
     *
     * The options are those of ul(), except that the tag is always `ol`.
     *
     * @param iterable<mixed> $items the list items
     * @param array<mixed> $options the tag attributes and the options of ul()
     * @return string the rendered list
     * @throws InvalidParamException when the `item` option is not callable, or a value has no text form
     */
    public function ol(iterable $items, array $options = []): string;
}
