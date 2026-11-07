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

use Hryvinskyi\Base\Api\Arrays\ArrayInspectorInterface;
use Hryvinskyi\Base\Api\Arrays\ObjectToArrayConverterInterface;
use Hryvinskyi\Base\Api\Html\ChoiceListRendererInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Api\Html\InputRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Renders select lists, checkbox lists and radio button lists.
 */
class ChoiceListRenderer implements ChoiceListRendererInterface
{
    /**
     * The options that configure a checkbox or radio list and are not attributes of its container.
     */
    private const INPUT_LIST_OPTION_KEYS = ['item', 'itemOptions', 'encode', 'separator', 'tag', 'strict'];

    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param InputRendererInterface $inputRenderer renders checkboxes, radio buttons and hidden inputs
     * @param HtmlEncoderInterface $htmlEncoder encodes labels
     * @param ObjectToArrayConverterInterface $objectToArrayConverter turns a selection into an array
     * @param ArrayInspectorInterface $arrayInspector looks a value up in a selection
     * @param TextConverter $textConverter turns labels, selections and option values into text
     * @param OptionReader $optionReader reads the options that are not attributes
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly InputRendererInterface $inputRenderer,
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly ObjectToArrayConverterInterface $objectToArrayConverter,
        private readonly ArrayInspectorInterface $arrayInspector,
        private readonly TextConverter $textConverter,
        private readonly OptionReader $optionReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function dropDownList(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string {
        if (!empty($options['multiple'])) {
            return $this->listBox($name, $selection, $items, $options);
        }

        $options['name'] = $name;
        unset($options['unselect']);

        return $this->renderSelect($selection, $items, $options);
    }

    /**
     * @inheritDoc
     */
    public function listBox(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string {
        if (!array_key_exists('size', $options)) {
            $options['size'] = 4;
        }
        if (!empty($options['multiple']) && !empty($name) && substr_compare($name, '[]', -2, 2) !== 0) {
            $name .= '[]';
        }
        $options['name'] = $name;

        $hidden = '';
        if (isset($options['unselect'])) {
            $hiddenName = !empty($name) && substr_compare($name, '[]', -2, 2) === 0 ? substr($name, 0, -2) : $name;
            $hidden = $this->renderUnselectInput($hiddenName, $options);
            unset($options['unselect']);
        }

        return $hidden . $this->renderSelect($selection, $items, $options);
    }

    /**
     * @inheritDoc
     */
    public function checkboxList(
        string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string {
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        return $this->renderInputList(
            $this->inputRenderer->checkbox(...),
            $name,
            substr($name, 0, -2),
            $selection,
            $items,
            $options
        );
    }

    /**
     * @inheritDoc
     */
    public function radioList(
        ?string $name,
        mixed $selection = null,
        array $items = [],
        array $options = []
    ): string {
        return $this->renderInputList($this->inputRenderer->radio(...), $name, $name, $selection, $items, $options);
    }

    /**
     * @inheritDoc
     */
    public function renderSelectOptions(mixed $selection, array $items, array $tagOptions = []): string
    {
        $selection = $this->normalizeSelection($selection);
        $encodeSpaces = (bool)$this->optionReader->value($tagOptions, 'encodeSpaces', false);
        $encode = (bool)$this->optionReader->value($tagOptions, 'encode', true);
        $strict = (bool)$this->optionReader->value($tagOptions, 'strict', false);
        $options = $this->optionReader->arrayValue($tagOptions, 'options');
        $groups = $this->optionReader->arrayValue($tagOptions, 'groups');

        $lines = [];
        if (isset($tagOptions['prompt'])) {
            $lines[] = $this->renderPromptOption($tagOptions['prompt'], $encode, $encodeSpaces);
        }

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $groupAttributes = $this->optionReader->arrayValue($groups, $key);
                if (!isset($groupAttributes['label'])) {
                    $groupAttributes['label'] = $key;
                }
                $content = $this->renderSelectOptions($selection, $value, [
                    'options' => $options,
                    'groups' => $groups,
                    'encodeSpaces' => $encodeSpaces,
                    'encode' => $encode,
                    'strict' => $strict,
                ]);
                $lines[] = $this->tagRenderer->tag('optgroup', "\n" . $content . "\n", $groupAttributes);
            } else {
                $attributes = $this->optionReader->arrayValue($options, $key);
                $attributes['value'] = (string)$key;
                if (!array_key_exists('selected', $attributes)) {
                    $attributes['selected'] = $this->isOptionSelected($key, $selection, $strict);
                }
                $text = $this->optionText($this->textConverter->toText($value), $encode, $encodeSpaces);
                $lines[] = $this->tagRenderer->tag('option', $text, $attributes);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Renders a `<select>` tag with its options; an empty one keeps the blank line between its tags.
     *
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items the option data
     * @param array<mixed> $options the tag attributes and the options listed in SELECT_OPTION_KEYS
     * @return string the rendered tag
     * @throws InvalidParamException when a label, a selected value or an attribute value has no text form
     */
    private function renderSelect(mixed $selection, array $items, array $options): string
    {
        return $this->tagRenderer->tag(
            'select',
            "\n" . $this->renderSelectOptions($selection, $items, $options) . "\n",
            array_diff_key($options, array_flip(self::SELECT_OPTION_KEYS))
        );
    }

    /**
     * Renders a checkbox or radio list.
     *
     * @param callable(?string,bool,array<mixed>):string $renderInput renders one input from its name, its checked
     *     state and its options
     * @param string|null $name the name of each input
     * @param string|null $hiddenName the name of the hidden input that carries the `unselect` value
     * @param mixed $selection the selected value or values
     * @param array<mixed> $items value => label
     * @param array<mixed> $options the container attributes and the list options
     * @return string the rendered list
     * @throws InvalidParamException when the `item` option is not callable, or a value has no text form
     */
    private function renderInputList(
        callable $renderInput,
        ?string $name,
        ?string $hiddenName,
        mixed $selection,
        array $items,
        array $options
    ): string {
        $selection = $this->listSelection($selection);
        $formatter = $this->optionReader->itemFormatter($options);
        $itemOptions = $this->optionReader->arrayValue($options, 'itemOptions');
        $encode = (bool)$this->optionReader->value($options, 'encode', true);
        $separator = $this->textConverter->toText($this->optionReader->value($options, 'separator', "\n"));
        $tag = $this->optionReader->tagName($options, 'div');
        $strict = (bool)$this->optionReader->value($options, 'strict', false);
        $options = array_diff_key($options, array_flip(self::INPUT_LIST_OPTION_KEYS));

        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            $checked = $this->isListItemChecked($value, $selection, $strict);
            if ($formatter !== null) {
                $lines[] = $this->textConverter->toText($formatter($index, $label, $name, $checked, $value));
            } else {
                $lines[] = $renderInput($name, $checked, $this->listItemOptions($value, $label, $encode, $itemOptions));
            }
            $index++;
        }

        $hidden = '';
        if (isset($options['unselect'])) {
            $hidden = $this->renderUnselectInput($hiddenName, $options);
            unset($options['unselect'], $options['disabled']);
        }

        return $hidden . $this->tagRenderer->tag($tag, implode($separator, $lines), $options);
    }

    /**
     * Builds the input options of one checkbox or radio list item; the item options override value and label.
     *
     * @param int|string $value the item value
     * @param mixed $label the item label
     * @param bool $encode whether to HTML-encode the label
     * @param array<mixed> $itemOptions the `itemOptions` of the list
     * @return array<mixed> the options of the input
     * @throws InvalidParamException when the label has no text form
     */
    private function listItemOptions(int|string $value, mixed $label, bool $encode, array $itemOptions): array
    {
        return array_merge([
            'value' => $value,
            'label' => $encode ? $this->htmlEncoder->encode($this->textConverter->toText($label)) : $label,
        ], $itemOptions);
    }

    /**
     * Renders the hidden input that submits the `unselect` value; a disabled list sends no value through it.
     *
     * @param string|null $name the name of the hidden input
     * @param array<mixed> $options the options of the list, with a non-null `unselect`
     * @return string the rendered tag
     * @throws InvalidParamException when the `unselect` value has no text form
     */
    private function renderUnselectInput(?string $name, array $options): string
    {
        $hiddenOptions = [];
        if (!empty($options['disabled'])) {
            $hiddenOptions['disabled'] = $options['disabled'];
        }

        return $this->inputRenderer->hiddenInput(
            $name,
            $this->textConverter->toText($options['unselect']),
            $hiddenOptions
        );
    }

    /**
     * Renders the prompt option of a select list.
     *
     * @param mixed $prompt the prompt text, or an array with the text under `text` and the attributes under `options`
     * @param bool $encode whether to HTML-encode the text
     * @param bool $encodeSpaces whether to replace the spaces of the text with `&nbsp;`
     * @return string the rendered tag
     * @throws InvalidParamException when the prompt text has no text form
     */
    private function renderPromptOption(mixed $prompt, bool $encode, bool $encodeSpaces): string
    {
        $attributes = ['value' => ''];
        if (is_array($prompt)) {
            $text = $this->textConverter->toText($prompt['text'] ?? null);
            $attributes = array_merge($attributes, $this->optionReader->arrayValue($prompt, 'options'));
        } else {
            $text = $this->textConverter->toText($prompt);
        }

        return $this->tagRenderer->tag('option', $this->optionText($text, $encode, $encodeSpaces), $attributes);
    }

    /**
     * Prepares the text of an option tag.
     *
     * @param string $text the prompt or label
     * @param bool $encode whether to HTML-encode the text
     * @param bool $encodeSpaces whether to replace its spaces with `&nbsp;`
     * @return string the text to print
     */
    private function optionText(string $text, bool $encode, bool $encodeSpaces): string
    {
        if ($encode) {
            $text = $this->htmlEncoder->encode($text);
        }

        return $encodeSpaces ? str_replace(' ', '&nbsp;', $text) : $text;
    }

    /**
     * Tells whether a select option is selected.
     *
     * An option with an empty value matches only an empty selection. A single selected value is compared as text
     * in strict mode and with PHP's loose comparison otherwise.
     *
     * @param int|string $key the option value
     * @param array<string>|string|int|float|null $selection the selection from normalizeSelection()
     * @param bool $strict whether the option value is compared with the selection as text
     * @return bool whether the option is selected
     */
    private function isOptionSelected(int|string $key, array|string|int|float|null $selection, bool $strict): bool
    {
        if ($selection === null) {
            return false;
        }
        if (is_array($selection)) {
            return $this->arrayInspector->isIn((string)$key, $selection, $strict);
        }
        if ($key === '' || $selection === '') {
            return $selection === $key;
        }

        return $strict ? (string)$key === (string)$selection : $selection == $key;
    }

    /**
     * Normalises the selection of a select list for comparison with the option values.
     *
     * An array or `Traversable` becomes a list of strings (a boolean as `1` or `0`), a boolean becomes `1` or `0`,
     * and a `Stringable` object its string. A value that cannot be compared with an option value selects nothing.
     *
     * @param mixed $selection the selection as given
     * @return array<string>|string|int|float|null the normalised selection
     * @throws InvalidParamException when a value in a selection list has no text form
     */
    private function normalizeSelection(mixed $selection): array|string|int|float|null
    {
        if (is_array($selection) || $selection instanceof \Traversable) {
            $normalizedSelection = [];
            foreach ($this->objectToArrayConverter->toArray($selection) as $selectionItem) {
                $normalizedSelection[] = is_bool($selectionItem)
                    ? $this->booleanText($selectionItem)
                    : $this->textConverter->toText($selectionItem);
            }

            return $normalizedSelection;
        }
        if (is_bool($selection)) {
            return $this->booleanText($selection);
        }
        if (is_string($selection) || is_int($selection) || is_float($selection)) {
            return $selection;
        }

        return $selection instanceof \Stringable ? (string)$selection : null;
    }

    /**
     * Returns `1` for true and `0` for false, the way a selected boolean matches an option value.
     *
     * @param bool $value the boolean
     * @return string its text
     */
    private function booleanText(bool $value): string
    {
        return $value ? '1' : '0';
    }

    /**
     * Normalises the selection of a checkbox or radio list.
     *
     * An array or `Traversable` becomes an array of strings; any other value but `null` becomes a string.
     *
     * @param mixed $selection the selection as given
     * @return array<string>|string|null the normalised selection
     * @throws InvalidParamException when a selected value has no text form
     */
    private function listSelection(mixed $selection): array|string|null
    {
        if (is_array($selection) || $selection instanceof \Traversable) {
            return $this->textConverter->texts($this->objectToArrayConverter->toArray($selection));
        }

        return $selection === null ? null : $this->textConverter->toText($selection);
    }

    /**
     * Tells whether an item of a checkbox or radio list is checked.
     *
     * @param int|string $value the item value
     * @param array<string>|string|null $selection the selection from listSelection()
     * @param bool $strict whether a selection list is compared with `===`
     * @return bool whether the item is checked
     */
    private function isListItemChecked(int|string $value, array|string|null $selection, bool $strict): bool
    {
        if ($selection === null) {
            return false;
        }
        if (is_array($selection)) {
            return $this->arrayInspector->isIn((string)$value, $selection, $strict);
        }

        return (string)$value === $selection;
    }
}
