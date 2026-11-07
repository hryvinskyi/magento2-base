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

namespace Hryvinskyi\Base\Api\Debug;

use Hryvinskyi\Base\Exception\InvalidValueException;

/**
 * Dumps variables for reading and exports them as PHP expressions.
 *
 * Unlike `var_dump()` and `print_r()`, the dump copes with objects that reference each other: an object met a
 * second time is written as a reference to its first occurrence.
 *
 * @api
 */
interface VarDumperInterface
{
    /**
     * Dumps a variable as readable text.
     *
     * Arrays are written as `[key => value]` blocks indented by four spaces per level, objects as `Class#n` with
     * their properties (or what their `__debugInfo()` returns) in `( [name] => value )` blocks, strings quoted with
     * quotes, backslashes and NUL bytes escaped, resources as `{resource}`. Anything deeper than `$depth` is
     * written as `[...]` or `Class(...)`.
     *
     * With `$highlight`, the result is HTML: the same text, every character of it HTML-escaped, wrapped in
     * `<pre><code>` and coloured with spans per PHP token. Removing the tags and decoding the entities gives back
     * the plain text, except that invalid UTF-8 bytes become U+FFFD, and no character of the dumped value reaches the
     * markup unescaped.
     *
     * @param mixed $var the variable to dump
     * @param int $depth the maximum depth the dump goes into nested arrays and objects
     * @param bool $highlight whether to return syntax-highlighted HTML instead of plain text
     * @return string the dump
     * @throws InvalidValueException when an object's `__debugInfo()` does not return an array
     */
    public function dumpAsString(mixed $var, int $depth = 10, bool $highlight = false): string;

    /**
     * Exports a variable as a PHP expression that evaluates back to the value.
     *
     * Arrays use the short syntax, one item per line with a trailing comma, keys left out for a list; scalars and
     * `null` read as `var_export()` writes them; a closure is written as its source text; any other object as
     * `unserialize('…')`. An object that cannot be serialized is written as the array of its items when it is
     * arrayable or an `IteratorAggregate`, otherwise as the quoted string of its `__toString()` or of its dump.
     *
     * @param mixed $var the variable to export
     * @return string the PHP expression
     * @throws InvalidValueException when the dump of an unserializable object meets a `__debugInfo()` that does
     *     not return an array
     */
    public function export(mixed $var): string;
}
