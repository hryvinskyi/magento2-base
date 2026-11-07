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

use Hryvinskyi\Base\Api\Html\JsRegexConverterInterface;

/**
 * Converts PHP regular expressions to JavaScript literals by rewriting escapes, delimiters and flags.
 */
class JsRegexConverter implements JsRegexConverterInterface
{
    /**
     * @inheritDoc
     */
    public function convert(string $regexp): string
    {
        $pattern = (string)preg_replace('/\\\\x\{?([0-9a-fA-F]+)\}?/', '\u$1', $regexp);
        $delimiter = substr($pattern, 0, 1);
        $closingDelimiter = (int)strrpos($pattern, $delimiter, 1);
        $flags = substr($pattern, $closingDelimiter + 1);
        if ($delimiter !== '/') {
            $pattern = '/' . str_replace('/', '\\/', substr($pattern, 1, $closingDelimiter - 1)) . '/';
        } else {
            $pattern = substr($pattern, 0, $closingDelimiter + 1);
        }
        if ($flags !== '') {
            $pattern .= preg_replace('/[^igmu]/', '', $flags);
        }

        return $pattern;
    }
}
