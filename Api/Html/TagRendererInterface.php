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
 * Renders HTML tags with their attributes.
 *
 * A `null` tag name means "no tag": tag() returns the content alone, beginTag() and endTag() return an empty
 * string. A caller whose container tag is optional passes the name it has, `null` included, instead of branching.
 *
 * @api
 */
interface TagRendererInterface
{
    /**
     * Renders a complete tag.
     *
     * A void element (`br`, `img`, `input`, … matched case-insensitively) ends in ` />` and drops the content.
     *
     * @param string|null $name the tag name, or `null` to return the content without a tag
     * @param string $content the content between the start and end tags, printed as given (not encoded)
     * @param array<mixed> $options the tag attributes, rendered by the attribute renderer
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function tag(?string $name, string $content = '', array $options = []): string;

    /**
     * Renders a start tag, also for a void element: `<br>`.
     *
     * @param string|null $name the tag name, or `null` for an empty string
     * @param array<mixed> $options the tag attributes, rendered by the attribute renderer
     * @return string the rendered start tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function beginTag(?string $name, array $options = []): string;

    /**
     * Renders an end tag, also for a void element: `</br>`.
     *
     * @param string|null $name the tag name, or `null` for an empty string
     * @return string the rendered end tag
     */
    public function endTag(?string $name): string;
}
