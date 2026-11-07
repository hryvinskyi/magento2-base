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

use Hryvinskyi\Base\Api\Html\FormRendererInterface;
use Hryvinskyi\Base\Api\Html\HtmlEncoderInterface;
use Hryvinskyi\Base\Api\Html\InputRendererInterface;
use Hryvinskyi\Base\Api\Html\TagRendererInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Renders forms, labels, buttons and text areas on top of the tag and input renderers.
 */
class FormRenderer implements FormRendererInterface
{
    /**
     * @param TagRendererInterface $tagRenderer renders the tags
     * @param InputRendererInterface $inputRenderer renders the hidden inputs of a form
     * @param HtmlEncoderInterface $htmlEncoder encodes the value of a text area
     * @param OptionReader $optionReader reads the options that are not attributes
     */
    public function __construct(
        private readonly TagRendererInterface $tagRenderer,
        private readonly InputRendererInterface $inputRenderer,
        private readonly HtmlEncoderInterface $htmlEncoder,
        private readonly OptionReader $optionReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function beginForm(
        string $action = '',
        string $method = 'post',
        ?string $formKey = null,
        array $options = []
    ): string {
        $hiddenInputs = [];
        if ($formKey !== null) {
            $hiddenInputs[] = $this->inputRenderer->hiddenInput('form_key', $formKey);
        }

        $queryPosition = strpos($action, '?');
        if ($queryPosition !== false && strcasecmp($method, 'get') === 0) {
            array_push($hiddenInputs, ...$this->queryInputs(substr($action, $queryPosition + 1)));
            $action = substr($action, 0, $queryPosition);
        }

        $options['action'] = $action;
        $options['method'] = $method;

        $form = $this->tagRenderer->beginTag('form', $options);

        return $hiddenInputs === [] ? $form : $form . "\n" . implode("\n", $hiddenInputs);
    }

    /**
     * @inheritDoc
     */
    public function endForm(): string
    {
        return '</form>';
    }

    /**
     * @inheritDoc
     */
    public function label(string $content, ?string $for = null, array $options = []): string
    {
        $options['for'] = $for;

        return $this->tagRenderer->tag('label', $content, $options);
    }

    /**
     * @inheritDoc
     */
    public function button(string $content = 'Button', array $options = []): string
    {
        if (!isset($options['type'])) {
            $options['type'] = 'button';
        }

        return $this->tagRenderer->tag('button', $content, $options);
    }

    /**
     * @inheritDoc
     */
    public function submitButton(string $content = 'Submit', array $options = []): string
    {
        $options['type'] = 'submit';

        return $this->button($content, $options);
    }

    /**
     * @inheritDoc
     */
    public function resetButton(string $content = 'Reset', array $options = []): string
    {
        $options['type'] = 'reset';

        return $this->button($content, $options);
    }

    /**
     * @inheritDoc
     */
    public function textarea(
        ?string $name,
        string|int|float|bool|\Stringable|null $value = '',
        array $options = []
    ): string {
        $options['name'] = $name;
        $doubleEncode = (bool)$this->optionReader->value($options, 'doubleEncode', true);
        unset($options['doubleEncode']);

        $content = $doubleEncode
            ? $this->htmlEncoder->encode((string)$value)
            : $this->htmlEncoder->encodeKeepingEntities((string)$value);

        return $this->tagRenderer->tag('textarea', $content, $options);
    }

    /**
     * Renders one hidden input per parameter of a query string; a parameter without `=` gets an empty value.
     *
     * @param string $query the query string, without the leading `?`
     * @return list<string> the rendered hidden inputs
     * @throws InvalidParamException when an attribute value has no text form
     */
    private function queryInputs(string $query): array
    {
        $inputs = [];
        foreach (explode('&', $query) as $pair) {
            $parts = explode('=', $pair, 2);
            $inputs[] = $this->inputRenderer->hiddenInput(urldecode($parts[0]), urldecode($parts[1] ?? ''));
        }

        return $inputs;
    }
}
