<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers\Fixture;

use Hryvinskyi\Base\Helper\ConsoleHelper;

/**
 * A console whose input is scripted, whose output is recorded and whose screen size is fixed.
 */
class ConsoleHelperScripted extends ConsoleHelper
{
    /**
     * Lines the user types, in order.
     *
     * @var list<string>
     */
    private static array $inputs = [];

    /**
     * Everything written to STDOUT.
     *
     * @var string
     */
    private static string $written = '';

    /**
     * The screen size reported, false for an unknown size.
     *
     * @var array{int,int}|false
     */
    private static $screen = false;

    /**
     * Starts a new script: the lines the user will type and the screen size.
     *
     * @param list<string> $inputs lines the user types, in order
     * @param array{int,int}|false $screen the width and height of the screen, false for an unknown size
     * @return void
     */
    public static function script(array $inputs, array|false $screen = false): void
    {
        self::$inputs = $inputs;
        self::$written = '';
        self::$screen = $screen;
    }

    /**
     * Returns everything written to STDOUT since the script started.
     *
     * @return string
     */
    public static function written(): string
    {
        return self::$written;
    }

    /**
     * Returns the next scripted line.
     *
     * @param bool $raw ignored: scripted lines carry no line ending
     * @return string
     * @throws \UnderflowException when the script has no line left, instead of waiting forever
     */
    public static function stdin($raw = false)
    {
        $line = array_shift(self::$inputs);
        if ($line === null) {
            throw new \UnderflowException('The scripted input is exhausted.');
        }

        return $line;
    }

    /**
     * Records the string instead of printing it.
     *
     * @param string $string the string to print
     * @return int the number of bytes recorded
     */
    public static function stdout($string)
    {
        self::$written .= $string;

        return strlen($string);
    }

    /**
     * Returns the scripted screen size.
     *
     * @param bool $refresh ignored: the size is fixed
     * @return array{int, int}|false
     */
    public static function getScreenSize($refresh = false)
    {
        return self::$screen;
    }
}
