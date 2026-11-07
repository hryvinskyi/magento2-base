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

use Hryvinskyi\Base\Exception\InvalidParamException;

/**
 * Reads the special options of the HTML element services.
 *
 * A key that is present counts even when its value is `null`: `'tag' => null` asks for no tag, while a missing `tag`
 * gives the default. Nothing here follows dot paths, so a key is always taken literally.
 *
 * Internal to the HTML services of this module.
 */
class OptionReader
{
    /**
     * @param TextConverter $textConverter turns a tag name that is not a string into text
     */
    public function __construct(
        private readonly TextConverter $textConverter
    ) {
    }

    /**
     * Returns the value under the key, or the default when the key is absent.
     *
     * @param array<mixed> $options the options
     * @param string $key the option key
     * @param mixed $default the value of an absent key
     * @return mixed the option value
     */
    public function value(array $options, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    /**
     * Returns the value under the key when it is an array, and an empty array otherwise.
     *
     * @param array<mixed> $options the options
     * @param int|string $key the option key
     * @return array<mixed> the option value
     */
    public function arrayValue(array $options, int|string $key): array
    {
        $value = $options[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * Returns the `tag` option as a tag name, `null` meaning no tag: `false` and `null` both ask for none.
     *
     * @param array<mixed> $options the options
     * @param string $default the tag name of an absent key
     * @return string|null the tag name
     * @throws InvalidParamException when the value has no text form
     */
    public function tagName(array $options, string $default): ?string
    {
        $tag = $this->value($options, 'tag', $default);

        return $tag === null || $tag === false ? null : $this->textConverter->toText($tag);
    }

    /**
     * Returns the `item` option, or `null` when it is absent or `null`.
     *
     * @param array<mixed> $options the options
     * @return (callable(mixed...): mixed)|null the callback
     * @throws InvalidParamException when the option is set to something that is not callable
     */
    public function itemFormatter(array $options): ?callable
    {
        $formatter = $options['item'] ?? null;
        if ($formatter === null || is_callable($formatter)) {
            return $formatter;
        }

        throw new InvalidParamException('The "item" option must be a callable.');
    }
}
