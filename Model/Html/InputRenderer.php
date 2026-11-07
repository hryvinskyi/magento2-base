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

use Hryvinskyi\Base\Api\Html\InputRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Renders input tags on top of the tag renderer.
 *
 * The label of a radio button or checkbox is rendered here with the tag renderer rather than by the form renderer,
 * which takes its hidden inputs from this class.
 */
class InputRenderer implements InputRendererInterface
{
    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param TextConverter $textConverter turns option values into text
     * @param OptionReader $optionReader reads the options that are not attributes
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly TextConverter $textConverter,
        private readonly OptionReader $optionReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function input(
        string $type,
        ?string $name = null,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string {
        if (!isset($options['type'])) {
            $options['type'] = $type;
        }
        $options['name'] = $name;
        $options['value'] = $value === null ? null : (string)$value;

        return $this->tagRenderer->tag('input', '', $options);
    }

    /**
     * @inheritDoc
     */
    public function buttonInput(?string $label = 'Button', array $options = []): string
    {
        return $this->labelledInput('button', $label, $options);
    }

    /**
     * @inheritDoc
     */
    public function submitInput(?string $label = 'Submit', array $options = []): string
    {
        return $this->labelledInput('submit', $label, $options);
    }

    /**
     * @inheritDoc
     */
    public function resetInput(?string $label = 'Reset', array $options = []): string
    {
        return $this->labelledInput('reset', $label, $options);
    }

    /**
     * @inheritDoc
     */
    public function textInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string {
        return $this->input('text', $name, $value, $options);
    }

    /**
     * @inheritDoc
     */
    public function hiddenInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string {
        return $this->input('hidden', $name, $value, $options);
    }

    /**
     * @inheritDoc
     */
    public function passwordInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string {
        return $this->input('password', $name, $value, $options);
    }

    /**
     * @inheritDoc
     */
    public function fileInput(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = null,
        array $options = []
    ): string {
        return $this->input('file', $name, $value, $options);
    }

    /**
     * @inheritDoc
     */
    public function radio(?string $name, bool $checked = false, array $options = []): string
    {
        return $this->booleanInput('radio', $name, $checked, $options);
    }

    /**
     * @inheritDoc
     */
    public function checkbox(?string $name, bool $checked = false, array $options = []): string
    {
        return $this->booleanInput('checkbox', $name, $checked, $options);
    }

    /**
     * Renders an input button of a fixed type, whose label is its value.
     *
     * @param string $type the type attribute
     * @param string|null $label the value attribute, or `null` for none
     * @param array<mixed> $options the tag attributes
     * @return string the rendered tag
     * @throws InvalidParamException when an attribute value has no text form
     */
    private function labelledInput(string $type, ?string $label, array $options): string
    {
        $options['type'] = $type;
        $options['value'] = $label;

        return $this->tagRenderer->tag('input', '', $options);
    }

    /**
     * Renders a radio button or a checkbox with its hidden unchecked value and its label.
     *
     * @param string $type the type attribute
     * @param string|null $name the name attribute, or `null` for none
     * @param bool $checked whether the input is checked, unless the options say so
     * @param array<mixed> $options the tag attributes and the options of radio() and checkbox()
     * @return string the rendered tags
     * @throws InvalidParamException when the value, `uncheck`, `label` or an attribute value has no text form
     */
    private function booleanInput(string $type, ?string $name, bool $checked, array $options): string
    {
        if (!isset($options['checked'])) {
            $options['checked'] = $checked;
        }
        $value = $this->optionReader->value($options, 'value', '1');
        $value = $value === null ? null : $this->textConverter->toText($value);

        $hidden = '';
        if (isset($options['uncheck'])) {
            $hidden = $this->hiddenInput(
                $name,
                $this->textConverter->toText($options['uncheck']),
                $this->uncheckedInputOptions($options)
            );
            unset($options['uncheck']);
        }

        if (!isset($options['label'])) {
            return $hidden . $this->input($type, $name, $value, $options);
        }

        $label = $this->textConverter->toText($options['label']);
        $labelOptions = $this->optionReader->arrayValue($options, 'labelOptions');
        $labelOptions['for'] = null;
        unset($options['label'], $options['labelOptions']);

        return $hidden . $this->tagRenderer->tag(
            'label',
            $this->input($type, $name, $value, $options) . ' ' . $label,
            $labelOptions
        );
    }

    /**
     * Returns the attributes the hidden unchecked input shares with its input, so a disabled input sends no value.
     *
     * @param array<mixed> $options the options of the radio button or checkbox
     * @return array<string, mixed> the attributes of the hidden input
     */
    private function uncheckedInputOptions(array $options): array
    {
        $hiddenOptions = [];
        if (isset($options['form'])) {
            $hiddenOptions['form'] = $options['form'];
        }
        if (!empty($options['disabled'])) {
            $hiddenOptions['disabled'] = $options['disabled'];
        }

        return $hiddenOptions;
    }
}
