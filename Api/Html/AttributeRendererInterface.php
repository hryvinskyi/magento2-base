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
 * Renders an array of HTML attributes as the text that follows a tag name.
 *
 * @api
 */
interface AttributeRendererInterface
{
    /**
     * Renders the attributes, each preceded by a space, or an empty string when nothing is rendered.
     *
     * - Attributes named in the configured attribute order come first, in that order; the rest keep their order.
     * - A `true` value renders a boolean attribute (` disabled`), `false` and `null` render nothing.
     * - An array under one of the configured data-attribute names (`aria`, `data`, `data-ng`, `ng` by default)
     *   renders one `name-key` attribute per element: a nested array as single-quoted HTML-safe JSON, a boolean as
     *   a boolean attribute, `null` not at all.
     * - A `class` array renders its classes separated by spaces, empty values dropped, and duplicates removed when
     *   class normalisation is on; an empty `class` array renders nothing.
     * - A `style` array renders as `property: value;` pairs; an empty one renders nothing.
     * - Any other array renders as single-quoted HTML-safe JSON.
     * - Every other value is HTML-encoded inside double quotes.
     *
     * @param array<mixed> $attributes attribute name => value
     * @return string the rendered attributes
     * @throws InvalidParamException when a value has no text form (an object without `__toString()`, a resource)
     */
    public function render(array $attributes): string;
}
