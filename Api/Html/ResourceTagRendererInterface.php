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
 * Renders the tags that load or carry styles and scripts.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * @api
 */
interface ResourceTagRendererInterface
{
    /**
     * Renders a style tag.
     *
     * @param string $content the style content, printed as given (not encoded)
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function style(string $content, array $options = []): string;

    /**
     * Renders a script tag.
     *
     * @param string $content the script content, printed as given (not encoded)
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function script(string $content, array $options = []): string;

    /**
     * Renders a link tag that refers to an external CSS file; `rel` is `stylesheet` unless the options set it.
     *
     * Two options are not rendered as attributes:
     *
     * - `condition`: a conditional comment such as `lt IE 9` that encloses the tag; a condition containing `!IE`
     *   gives the form browsers other than IE read as well.
     * - `noscript`: `true` encloses the tag in `<noscript>`. It is ignored when `condition` is set.
     *
     * @param string $url the URL of the CSS file, printed as given (HTML-encoded)
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered tag
     * @throws InvalidParamException when the condition or an attribute value has no text form
     */
    public function cssFile(string $url, array $options = []): string;

    /**
     * Renders a script tag that refers to an external JavaScript file.
     *
     * The `condition` option is not rendered as an attribute: a conditional comment such as `lt IE 9` that encloses
     * the tag; a condition containing `!IE` gives the form browsers other than IE read as well.
     *
     * @param string $url the URL of the JavaScript file, printed as given (HTML-encoded)
     * @param array<mixed> $options the tag attributes and the option above
     * @return string the rendered tag
     * @throws InvalidParamException when the condition or an attribute value has no text form
     */
    public function jsFile(string $url, array $options = []): string;
}
