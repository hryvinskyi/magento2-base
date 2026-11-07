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
 * Renders forms and the form elements that are not inputs.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * @api
 */
interface FormRendererInterface
{
    /**
     * Renders a form start tag, followed on new lines by its hidden inputs.
     *
     * For the `get` method, in any case, the query parameters of the action become hidden inputs and leave the
     * action, because a browser drops the query string of a GET form's action.
     *
     * @param string $action the form action URL, printed as given (HTML-encoded)
     * @param string $method the form submission method, printed as given
     * @param string|null $formKey the Magento form key: when given, a hidden `form_key` input carries it
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag and its hidden inputs
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function beginForm(
        string $action = '',
        string $method = 'post',
        ?string $formKey = null,
        array $options = []
    ): string;

    /**
     * Renders a form end tag.
     *
     * @return string the rendered tag
     */
    public function endForm(): string;

    /**
     * Renders a label tag.
     *
     * @param string $content the label text, printed as given (not encoded): encode text that comes from end users
     * @param string|null $for the id of the element the label belongs to, or `null` for no `for` attribute; it
     *     replaces any `for` in the options
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function label(string $content, ?string $for = null, array $options = []): string;

    /**
     * Renders a button tag; `type` is `button` unless the options set it.
     *
     * @param string $content the button content, printed as given (not encoded): encode text that comes from end
     *     users
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function button(string $content = 'Button', array $options = []): string;

    /**
     * Renders a submit button tag.
     *
     * Avoid the names `submit`, `length` and `method` for a submit button: they hide properties of the form element
     * in the browser's scripts.
     *
     * @param string $content the button content, printed as given (not encoded): encode text that comes from end
     *     users
     * @param array<mixed> $options the tag attributes; `type` is always `submit`
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function submitButton(string $content = 'Submit', array $options = []): string;

    /**
     * Renders a reset button tag.
     *
     * @param string $content the button content, printed as given (not encoded): encode text that comes from end
     *     users
     * @param array<mixed> $options the tag attributes; `type` is always `reset`
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function resetButton(string $content = 'Reset', array $options = []): string;

    /**
     * Renders a text area with its value HTML-encoded.
     *
     * The `doubleEncode` option is not rendered as an attribute: `false` leaves the entities already in the value
     * as they are, otherwise they are encoded again.
     *
     * @param string|null $name the input name, or `null` for no `name` attribute
     * @param string|int|float|bool|\Stringable|null $value the input value, cast to a string
     * @param array<mixed> $options the tag attributes and the option above
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function textarea(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = '',
        array $options = []
    ): string;
}
