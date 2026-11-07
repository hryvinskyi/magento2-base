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
 * Renders input tags.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * @api
 */
interface InputRendererInterface
{
    /**
     * Renders an input of the given type; a `type` in the options takes priority.
     *
     * @param string $type the type attribute
     * @param string|null $name the name attribute, or `null` for none
     * @param string|int|float|bool|\Stringable|null $value the value attribute cast to a string, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function input(
        string $type,
        ?string $name = null,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string;

    /**
     * Renders an input button.
     *
     * @param string|null $label the value attribute, or `null` for none
     * @param array<mixed> $options the tag attributes; `type` is always `button`
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function buttonInput(?string $label = 'Button', array $options = []): string;

    /**
     * Renders a submit input button.
     *
     * Avoid the names `submit`, `length` and `method` for a submit button: they hide properties of the form element
     * in the browser's scripts.
     *
     * @param string|null $label the value attribute, or `null` for none
     * @param array<mixed> $options the tag attributes; `type` is always `submit`
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function submitInput(?string $label = 'Submit', array $options = []): string;

    /**
     * Renders a reset input button.
     *
     * @param string|null $label the value attribute, or `null` for none
     * @param array<mixed> $options the tag attributes; `type` is always `reset`
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function resetInput(?string $label = 'Reset', array $options = []): string;

    /**
     * Renders a text input field.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param string|int|float|bool|\Stringable|null $value the value attribute cast to a string, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function textInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string;

    /**
     * Renders a hidden input field.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param string|int|float|bool|\Stringable|null $value the value attribute cast to a string, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function hiddenInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string;

    /**
     * Renders a password input field.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param string|int|float|bool|\Stringable|null $value the value attribute cast to a string, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function passwordInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string;

    /**
     * Renders a file input field; the enclosing form needs the `multipart/form-data` enctype.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param string|int|float|bool|\Stringable|null $value the value attribute cast to a string, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    public function fileInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string;

    /**
     * Renders a radio button.
     *
     * Besides the attributes, the options take the following; the first three are not rendered as attributes:
     *
     * - `uncheck`: the value submitted when the button is not checked, carried by a hidden input printed before it.
     * - `label`: text displayed next to the button, printed as given (not encoded), which encloses the button in a
     *   label tag.
     * - `labelOptions`: the attributes of that label tag; its `for` is not rendered.
     * - `value`: the value attribute, `1` when absent and none when `null`.
     * - `checked`: takes priority over `$checked`.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param bool $checked whether the button is checked
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered tags
     * @throws InvalidParamException when the value, `uncheck`, `label` or an attribute value has no text form
     */
    public function radio(?string $name, bool $checked = false, array $options = []): string;

    /**
     * Renders a checkbox.
     *
     * Besides the attributes, the options take the following; the first three are not rendered as attributes:
     *
     * - `uncheck`: the value submitted when the box is not checked, carried by a hidden input printed before it.
     * - `label`: text displayed next to the box, printed as given (not encoded), which encloses the box in a label
     *   tag.
     * - `labelOptions`: the attributes of that label tag; its `for` is not rendered.
     * - `value`: the value attribute, `1` when absent and none when `null`.
     * - `checked`: takes priority over `$checked`.
     *
     * @param string|null $name the name attribute, or `null` for none
     * @param bool $checked whether the box is checked
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered tags
     * @throws InvalidParamException when the value, `uncheck`, `label` or an attribute value has no text form
     */
    public function checkbox(?string $name, bool $checked = false, array $options = []): string;
}
