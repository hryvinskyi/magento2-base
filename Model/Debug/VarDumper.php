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

namespace Hryvinskyi\Base\Model\Debug;

use Hryvinskyi\Base\Api\Data\ArrayableInterface;
use Hryvinskyi\Base\Api\Debug\VarDumperInterface;
use Hryvinskyi\Base\Exception\InvalidValueException;
use Magento\Framework\Serialize\Serializer\Serialize;

/**
 * Dumps variables as readable text and exports them as PHP expressions.
 *
 * The service is shared, so it keeps nothing between calls: the text being built is returned up the recursion,
 * and the objects already met in a dump live in a list local to that dump. Exporting an object that cannot be
 * serialized starts a dump of its own, numbered from `#1`, without disturbing the export around it.
 */
class VarDumper implements VarDumperInterface
{
    /**
     * Indentation added per nesting level.
     */
    private const INDENT = '    ';

    /**
     * @param Serialize $serializer writes the `unserialize('…')` form of an object; PHP's native serialization is
     *     the format that form needs, which no serializer contract promises
     * @param ClosureSourceReader $closureSourceReader writes a closure as its source text
     * @param PhpTokenHighlighter $highlighter turns a dump into highlighted HTML
     */
    public function __construct(
        private readonly Serialize $serializer,
        private readonly ClosureSourceReader $closureSourceReader,
        private readonly PhpTokenHighlighter $highlighter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function dumpAsString(mixed $var, int $depth = 10, bool $highlight = false): string
    {
        $objects = [];
        $dump = $this->dumpValue($var, 0, $depth, $objects);

        return $highlight ? $this->highlighter->highlight($dump) : $dump;
    }

    /**
     * @inheritDoc
     */
    public function export(mixed $var): string
    {
        return $this->exportValue($var, 0);
    }

    /**
     * Returns the readable form of a variable.
     *
     * @param mixed $var the variable to dump
     * @param int $level the nesting level of the variable
     * @param int $depth the maximum depth of the dump
     * @param list<object> $objects the objects already dumped in this dump, in the order they were met
     * @param-out list<object> $objects
     * @return string the dump of the variable
     * @throws InvalidValueException when an object's `__debugInfo()` does not return an array
     */
    private function dumpValue(mixed $var, int $level, int $depth, array &$objects): string
    {
        if (is_array($var)) {
            return $this->dumpArray($var, $level, $depth, $objects);
        }
        if (is_object($var)) {
            return $this->dumpObject($var, $level, $depth, $objects);
        }

        return $this->dumpScalar($var);
    }

    /**
     * Returns the readable form of an array: one `key => value` line per item, or `[...]` beyond the depth.
     *
     * @param array<mixed> $var the array to dump
     * @param int $level the nesting level of the array
     * @param int $depth the maximum depth of the dump
     * @param list<object> $objects the objects already dumped in this dump, in the order they were met
     * @param-out list<object> $objects
     * @return string the dump of the array
     * @throws InvalidValueException when an object's `__debugInfo()` does not return an array
     */
    private function dumpArray(array $var, int $level, int $depth, array &$objects): string
    {
        if ($depth <= $level) {
            return '[...]';
        }
        if ($var === []) {
            return '[]';
        }

        $spaces = str_repeat(self::INDENT, $level);
        $output = '[';
        foreach ($var as $key => $value) {
            $output .= "\n" . $spaces . self::INDENT . $this->dumpScalar($key) . ' => '
                . $this->dumpValue($value, $level + 1, $depth, $objects);
        }

        return $output . "\n" . $spaces . ']';
    }

    /**
     * Returns the readable form of an object.
     *
     * The object is written as `Class#n` with its properties, as `Class#n(...)` when it was already dumped as
     * number n, or as `Class(...)` beyond the depth.
     *
     * @param object $var the object to dump
     * @param int $level the nesting level of the object
     * @param int $depth the maximum depth of the dump
     * @param list<object> $objects the objects already dumped in this dump, in the order they were met
     * @param-out list<object> $objects
     * @return string the dump of the object
     * @throws InvalidValueException when an object's `__debugInfo()` does not return an array
     */
    private function dumpObject(object $var, int $level, int $depth, array &$objects): string
    {
        $className = get_class($var);
        $seenAt = array_search($var, $objects, true);
        if ($seenAt !== false) {
            return $className . '#' . ($seenAt + 1) . '(...)';
        }
        if ($depth <= $level) {
            return $className . '(...)';
        }

        $objects[] = $var;
        $id = count($objects);
        $spaces = str_repeat(self::INDENT, $level);
        $output = $className . '#' . $id . "\n" . $spaces . '(';
        foreach ($this->debugValues($var) as $key => $value) {
            $keyDisplay = strtr(trim((string) $key), "\0", ':');
            $output .= "\n" . $spaces . self::INDENT . '[' . $keyDisplay . '] => '
                . $this->dumpValue($value, $level + 1, $depth, $objects);
        }

        return $output . "\n" . $spaces . ')';
    }

    /**
     * Returns the values a dump shows for an object: what its `__debugInfo()` returns, or else all its properties.
     *
     * @param object $var the object
     * @return array<mixed> the values by property name; non-public names carry NUL-separated scope prefixes
     * @throws InvalidValueException when the object's `__debugInfo()` does not return an array
     */
    private function debugValues(object $var): array
    {
        $values = !$var instanceof \__PHP_Incomplete_Class && method_exists($var, '__debugInfo')
            ? $var->__debugInfo()
            : (array) $var;
        if (!is_array($values)) {
            throw new InvalidValueException('__debuginfo() must return an array');
        }

        return $values;
    }

    /**
     * Returns the readable form of a value that is neither an array nor an object.
     *
     * @param mixed $var the value to dump
     * @return string the dump of the value; a closed resource dumps as an empty string
     */
    private function dumpScalar(mixed $var): string
    {
        return match (true) {
            is_bool($var) => $var ? 'true' : 'false',
            is_int($var), is_float($var) => (string) $var,
            // quotes, backslashes and NUL bytes are escaped the way a quoted PHP string literal reads them
            is_string($var) => "'" . strtr($var, ['\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\0" => '\\0']) . "'",
            is_resource($var) => '{resource}',
            $var === null => 'null',
            default => '',
        };
    }

    /**
     * Returns a variable as a PHP expression.
     *
     * @param mixed $var the variable to export
     * @param int $level the nesting level of the variable
     * @return string the PHP expression
     * @throws InvalidValueException when the dump of an unserializable object meets a `__debugInfo()` that does
     *     not return an array
     */
    private function exportValue(mixed $var, int $level): string
    {
        if ($var === null) {
            return 'null';
        }
        if (is_array($var)) {
            return $this->exportArray($var, $level);
        }
        if (!is_object($var)) {
            return var_export($var, true);
        }
        if ($var instanceof \Closure) {
            return $this->closureSourceReader->read($var);
        }

        return $this->exportObject($var, $level);
    }

    /**
     * Returns an array as a short-syntax PHP array.
     *
     * Each item goes on its own line with a trailing comma; a list is written without its keys.
     *
     * @param array<mixed> $var the array to export
     * @param int $level the nesting level of the array
     * @return string the PHP expression
     * @throws InvalidValueException when the dump of an unserializable object meets a `__debugInfo()` that does
     *     not return an array
     */
    private function exportArray(array $var, int $level): string
    {
        if ($var === []) {
            return '[]';
        }

        $outputKeys = !array_is_list($var);
        $spaces = str_repeat(self::INDENT, $level);
        $output = '[';
        foreach ($var as $key => $value) {
            $output .= "\n" . $spaces . self::INDENT;
            if ($outputKeys) {
                $output .= $this->exportValue($key, 0) . ' => ';
            }
            $output .= $this->exportValue($value, $level + 1) . ',';
        }

        return $output . "\n" . $spaces . ']';
    }

    /**
     * Returns an object as `unserialize('…')`.
     *
     * An object that cannot be serialized is written as the array of its items, or as the quoted string of its
     * text or of its dump.
     *
     * @param object $var the object to export
     * @param int $level the nesting level of the object
     * @return string the PHP expression
     * @throws InvalidValueException when the dump of the object meets a `__debugInfo()` that does not return an
     *     array
     */
    private function exportObject(object $var, int $level): string
    {
        try {
            return 'unserialize(' . var_export($this->serializer->serialize($var), true) . ')';
        } catch (\Exception) {
            // serialization fails for an object holding a closure, or one that refuses to be serialized
            if ($var instanceof ArrayableInterface) {
                return $this->exportValue($var->toArray(), $level);
            }
            if ($var instanceof \IteratorAggregate) {
                return $this->exportValue(iterator_to_array($var), $level);
            }

            return var_export($var instanceof \Stringable ? $var->__toString() : $this->dumpAsString($var), true);
        }
    }
}
