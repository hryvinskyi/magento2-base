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
 * Renders the controls that offer a choice: select lists, checkbox lists and radio button lists.
 *
 * Attributes go through the attribute renderer: values are HTML-encoded and a `null` attribute is not rendered.
 *
 * Wherever a selection is taken, it is a scalar for a single value, or an array or `Traversable` for several.
 *
 * @api
 */
interface ChoiceListRendererInterface
{
    /**
     * The options renderSelectOptions() reads. A caller that renders its own `<select>` with the same options strips
     * these keys from the attributes, or they are printed on the tag.
     */
    public const SELECT_OPTION_KEYS = ['prompt', 'options', 'groups', 'encode', 'encodeSpaces', 'strict'];

    /**
     * Renders a drop-down list; with a non-empty `multiple` option, the list is rendered by listBox().
     *
     * Empty items keep the blank line between the tags. See renderSelectOptions() for the items and for the options
     * listed in SELECT_OPTION_KEYS, which are not rendered as attributes; neither is `unselect`.
     *
     * @param string|null $name the input name, or `null` for no `name` attribute
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items the option data
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered tag
     * @throws InvalidParamException when a label, a selected value or an attribute value has no text form
     */
    public function dropDownList(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string;

    /**
     * Renders a list box, four rows high unless the options set `size`; `[]` is added to the name of a multiple list.
     *
     * Empty items keep the blank line between the tags. See renderSelectOptions() for the items and for the options
     * listed in SELECT_OPTION_KEYS, which are not rendered as attributes. Neither is `unselect`: the value submitted
     * when nothing is selected, carried by a hidden input printed before the tag.
     *
     * @param string|null $name the input name, or `null` for no `name` attribute
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items the option data
     * @param array<mixed> $options the tag attributes and the options above
     * @return string the rendered tags
     * @throws InvalidParamException when a label, a selected value or an attribute value has no text form
     */
    public function listBox(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string;

    /**
     * Renders a list of checkboxes inside a container tag; `[]` is added to the name unless it is there.
     *
     * The options are the attributes of the container, except the following:
     *
     * - `tag`: the container's tag name, `div` when absent; `false` or `null` renders no container.
     * - `unselect`: the value submitted when no box is checked, carried by a hidden input printed first, which also
     *   takes the `disabled` attribute from the container.
     * - `encode`: whether the labels are HTML-encoded, `true` when absent; ignored when `item` is set.
     * - `strict`: whether a selected value is compared with `===`, `false` when absent.
     * - `separator`: the HTML between the items, a newline when absent.
     * - `itemOptions`: options of each checkbox, overriding its value and label; see
     *   InputRendererInterface::checkbox().
     * - `item`: a callback that renders one item instead of the checkbox. `$index` is the zero-based position of
     *   the item in the list, `$value` its key in `$items`:
     *   `callable(int $index, mixed $label, string $name, bool $checked, int|string $value): mixed`.
     *
     * @param string $name the name of each checkbox
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items value => label
     * @param array<mixed> $options the container attributes and the options above
     * @return string the rendered list
     * @throws InvalidParamException when the `item` option is not callable, or a value has no text form
     */
    public function checkboxList(
        string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string;

    /**
     * Renders a list of radio buttons inside a container tag.
     *
     * The options are the attributes of the container, except the following:
     *
     * - `tag`: the container's tag name, `div` when absent; `false` or `null` renders no container.
     * - `unselect`: the value submitted when no button is checked, carried by a hidden input printed first, which
     *   also takes the `disabled` attribute from the container.
     * - `encode`: whether the labels are HTML-encoded, `true` when absent; ignored when `item` is set.
     * - `strict`: whether a selected value is compared with `===`, `false` when absent.
     * - `separator`: the HTML between the items, a newline when absent.
     * - `itemOptions`: options of each radio button, overriding its value and label; see
     *   InputRendererInterface::radio().
     * - `item`: a callback that renders one item instead of the radio button. `$index` is the zero-based position
     *   of the item in the list, `$value` its key in `$items`:
     *   `callable(int $index, mixed $label, ?string $name, bool $checked, int|string $value): mixed`.
     *
     * @param string|null $name the name of each radio button, or `null` for no `name` attribute
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items value => label
     * @param array<mixed> $options the container attributes and the options above
     * @return string the rendered list
     * @throws InvalidParamException when the `item` option is not callable, or a value has no text form
     */
    public function radioList(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string;

    /**
     * Renders the option tags of a select list, one per line.
     *
     * Keys of `$items` are the option values and its values the labels, both HTML-encoded by default. A value that
     * is an array gives an optgroup labelled with its key, whose items follow the same rules.
     *
     * `$tagOptions` is only read, never changed. Its other keys are ignored, so it can be the options of the
     * `<select>` itself; strip SELECT_OPTION_KEYS before rendering that tag. These keys are read:
     *
     * - `prompt`: the text of a first option with an empty value, or `['text' => …, 'options' => […]]`, whose
     *   `options` override the value and add attributes.
     * - `options`: option value => attributes of that option tag. A `selected` attribute replaces the comparison with
     *   the selection.
     * - `groups`: optgroup label => attributes of that optgroup tag.
     * - `encode`: whether the prompt and the labels are HTML-encoded, `true` when absent.
     * - `encodeSpaces`: whether their spaces become `&nbsp;`, `false` when absent.
     * - `strict`: whether a selected value is compared as text with `===`, `false` when absent.
     *
     * @param mixed $selection the selected value or values; a value that is neither scalar nor text selects nothing
     * @param array<mixed> $items the option data
     * @param array<mixed> $tagOptions the options above
     * @return string the rendered option tags
     * @throws InvalidParamException when a label or a value in a selection list has no text form
     */
    public function renderSelectOptions(mixed $selection, array $items, array $tagOptions = []): string;
}
