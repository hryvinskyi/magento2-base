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

/**
 * Converts a PHP regular expression into a JavaScript regular expression literal.
 *
 * @api
 */
interface JsRegexConverterInterface
{
    /**
     * Converts the expression to JavaScript syntax.
     *
     * `\x` escapes become `\u` escapes, any delimiter becomes `/` (a `/` inside the pattern is escaped), and only
     * the `i`, `g`, `m` and `u` flags are kept.
     *
     * `([a-z0-9-]+)` gives `/[a-z0-9-]+/`; `/([a-z0-9-]+)/Ugimex` gives `/([a-z0-9-]+)/gim`.
     *
     * @param string $regexp the PHP regular expression, delimiters and flags included
     * @return string the JavaScript regular expression literal
     */
    public function convert(string $regexp): string;
}
