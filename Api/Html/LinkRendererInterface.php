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
 * Renders hyperlinks and images.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * @api
 */
interface LinkRendererInterface
{
    /**
     * Renders a hyperlink.
     *
     * @param string $text the link body, printed as given (not encoded): encode text that comes from end users
     * @param string|null $url the URL, printed as given (HTML-encoded), or `null` for no `href` attribute
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function a(string $text, ?string $url = null, array $options = []): string;

    /**
     * Renders a mailto hyperlink.
     *
     * @param string $text the link body, printed as given (not encoded): encode text that comes from end users
     * @param string|null $email the email address, or `null` to use the link body as the address
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function mailto(string $text, ?string $email = null, array $options = []): string;

    /**
     * Renders an image tag; an empty `alt` is added when the options have none.
     *
     * A `srcset` option given as an array of descriptor => URL renders as a comma-separated list of `URL descriptor`
     * pairs: `['2x' => '/image-2x.png']` gives `/image-2x.png 2x`.
     *
     * @param string $src the image URL, printed as given (HTML-encoded)
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when a `srcset` URL or an attribute value has no text form
     */
    public function img(string $src, array $options = []): string;
}
