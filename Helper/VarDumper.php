<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * @copyright Copyright (c) 2019 Volodymyr Hryvinskyi. All rights reserved.
 * @author Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 * @github <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * Dumps variables for reading and exports them as PHP expressions.
 */
class VarDumper
{
    /**
     * Objects already dumped in the current dump; a repeated one is printed as a reference.
     *
     * @var list<object>
     */
    private static $_objects = [];

    /**
     * Output of the dump or export in progress.
     *
     * @var string
     */
    private static $_output = '';

    /**
     * Maximum depth of the dump in progress.
     *
     * @var int
     */
    private static $_depth = 10;

    /**
     * Displays a variable.
     *
     * This method achieves the similar functionality as var_dump and print_r
     * but is more robust when handling complex objects such as objects that reference each other.
     *
     * @param mixed $var variable to be dumped
     * @param int $depth maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight whether the result should be syntax-highlighted
     * @return void
     */
    public static function dump($var, $depth = 10, $highlight = false)
    {
        echo static::dumpAsString($var, $depth, $highlight);
    }

    /**
     * Dumps a variable in terms of a string.
     *
     * This method achieves the similar functionality as var_dump and print_r
     * but is more robust when handling complex objects such as objects that reference each other.
     *
     * @param mixed $var variable to be dumped
     * @param int $depth maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight whether the result should be syntax-highlighted
     * @return string the string representation of the variable
     */
    public static function dumpAsString($var, $depth = 10, $highlight = false)
    {
        self::$_output = '';
        self::$_objects = [];
        self::$_depth = $depth;
        self::dumpInternal($var, 0);
        if ($highlight) {
            $result = highlight_string("<?php\n" . self::$_output, true);
            self::$_output = (string) preg_replace('/&lt;\\?php(?:<br \\/>|\\n)/', '', $result, 1);
        }
        return self::$_output;
    }

    /**
     * Exports a variable as a string representation.
     *
     * The string is a valid PHP expression that can be evaluated by PHP parser
     * and the evaluation result will give back the variable value.
     *
     * This method is similar to `var_export()`. The main difference is that
     * it generates more compact string representation using short array syntax.
     *
     * It also handles objects by using the PHP functions serialize() and unserialize().
     *
     * @param mixed $var the variable to be exported.
     * @return string a string representation of the variable
     */
    public static function export($var)
    {
        self::$_output = '';
        self::exportInternal($var, 0);
        return self::$_output;
    }

    /**
     * Appends the readable form of a variable to the output; an object already dumped is printed as a reference.
     *
     * @param mixed $var variable to be dumped
     * @param int $level depth level
     * @return void
     * @throws InvalidValueException when an object's __debugInfo() does not return an array
     */
    private static function dumpInternal(mixed $var, int $level): void
    {
        if (is_array($var)) {
            if (self::$_depth <= $level) {
                self::$_output .= '[...]';

                return;
            }
            if (empty($var)) {
                self::$_output .= '[]';

                return;
            }
            $spaces = str_repeat(' ', $level * 4);
            self::$_output .= '[';
            foreach ($var as $key => $value) {
                self::$_output .= "\n" . $spaces . '    ';
                self::dumpInternal($key, 0);
                self::$_output .= ' => ';
                self::dumpInternal($value, $level + 1);
            }
            self::$_output .= "\n" . $spaces . ']';

            return;
        }
        if (is_object($var)) {
            $className = get_class($var);
            $id = array_search($var, self::$_objects, true);
            if ($id !== false) {
                self::$_output .= $className . '#' . ($id + 1) . '(...)';

                return;
            }
            if (self::$_depth <= $level) {
                self::$_output .= $className . '(...)';

                return;
            }
            $id = array_push(self::$_objects, $var);
            $spaces = str_repeat(' ', $level * 4);
            self::$_output .= "$className#$id\n" . $spaces . '(';
            $dumpValues = !$var instanceof \__PHP_Incomplete_Class && method_exists($var, '__debugInfo')
                ? $var->__debugInfo()
                : (array) $var;
            if (!is_array($dumpValues)) {
                throw new InvalidValueException('__debuginfo() must return an array');
            }
            foreach ($dumpValues as $key => $value) {
                $keyDisplay = strtr(trim((string) $key), "\0", ':');
                self::$_output .= "\n" . $spaces . "    [$keyDisplay] => ";
                self::dumpInternal($value, $level + 1);
            }
            self::$_output .= "\n" . $spaces . ')';

            return;
        }

        self::$_output .= match (true) {
            is_bool($var) => $var ? 'true' : 'false',
            is_int($var), is_float($var) => (string) $var,
            // quotes, backslashes and NUL bytes are escaped the way a quoted PHP string literal reads them
            is_string($var) => "'" . strtr($var, ['\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\0" => '\\0']) . "'",
            is_resource($var) => '{resource}',
            $var === null => 'null',
            // a closed resource
            default => '',
        };
    }

    /**
     * Appends a variable as a PHP expression to the output.
     *
     * An array is written in short syntax, a list without keys; a closure as its source; any other object
     * serialized. An object that cannot be serialized is written as an array of its items when it is Arrayable
     * or IteratorAggregate, as the string of its __toString(), or else as the string of its dump.
     *
     * @param mixed $var variable to be exported
     * @param int $level depth level
     * @return void
     */
    private static function exportInternal(mixed $var, int $level): void
    {
        if ($var === null) {
            self::$_output .= 'null';

            return;
        }
        if (is_array($var)) {
            if (empty($var)) {
                self::$_output .= '[]';

                return;
            }
            $outputKeys = (array_keys($var) !== range(0, count($var) - 1));
            $spaces = str_repeat(' ', $level * 4);
            self::$_output .= '[';
            foreach ($var as $key => $value) {
                self::$_output .= "\n" . $spaces . '    ';
                if ($outputKeys) {
                    self::exportInternal($key, 0);
                    self::$_output .= ' => ';
                }
                self::exportInternal($value, $level + 1);
                self::$_output .= ',';
            }
            self::$_output .= "\n" . $spaces . ']';

            return;
        }
        if (!is_object($var)) {
            self::$_output .= var_export($var, true);

            return;
        }
        if ($var instanceof \Closure) {
            self::$_output .= self::exportClosure($var);

            return;
        }

        try {
            $output = 'unserialize(' . var_export(serialize($var), true) . ')';
        } catch (\Exception $e) {
            // serialize may fail, for example: if object contains a `\Closure` instance
            if ($var instanceof Arrayable) {
                self::exportInternal($var->toArray(), $level);

                return;
            }
            if ($var instanceof \IteratorAggregate) {
                self::exportInternal(iterator_to_array($var), $level);

                return;
            }
            $outputBackup = self::$_output;
            $output = var_export($var instanceof \Stringable ? $var->__toString() : self::dumpAsString($var), true);
            self::$_output = $outputBackup;
        }
        self::$_output .= $output;
    }

    /**
     * Exports a Closure instance as its source code.
     *
     * @param \Closure $closure closure instance.
     * @return string
     */
    private static function exportClosure(\Closure $closure): string
    {
        $reflection = new \ReflectionFunction($closure);
        $fileName = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();
        $lines = $fileName === false ? false : file($fileName);
        if ($lines === false || $start === false || $end === false) {
            return 'function() {/* Error: unable to determine Closure source */}';
        }
        --$start;
        $source = implode('', array_slice($lines, $start, $end - $start));
        $tokens = \PhpToken::tokenize('<?php ' . $source);
        array_shift($tokens);
        $closureTokens = [];
        $pendingParenthesisCount = 0;
        foreach ($tokens as $token) {
            if ($token->id === T_FUNCTION) {
                $closureTokens[] = $token->text;
                continue;
            }
            if ($closureTokens !== []) {
                $closureTokens[] = $token->text;
                if ($token->id === ord('}')) {
                    $pendingParenthesisCount--;
                    if ($pendingParenthesisCount === 0) {
                        break;
                    }
                } elseif ($token->id === ord('{')) {
                    $pendingParenthesisCount++;
                }
            }
        }
        return implode('', $closureTokens);
    }
}
