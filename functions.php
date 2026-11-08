<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

use Hryvinskyi\Base\Api\Debug\VarDumperInterface;
use Magento\Framework\App\ObjectManager;

/*
 * Debugging tools that need no import and no injection, for a dump from anywhere: a class, a template, a script.
 * They ask the object manager for the dumper because a global function has no constructor; they have no check of
 * their own and print in production mode too, so none of them belongs in committed code.
 */

if (!function_exists('hdump')) {
    /**
     * Prints Base's dump of a value and returns the value, so a call can wrap an expression.
     *
     * The dump is `VarDumperInterface::dumpAsString()`: it copes with objects that reference each other, and
     * highlighted it is HTML with every token escaped. Before Magento has started there is no dumper to ask, and the
     * value is printed with `print_r()`.
     *
     * @param mixed $var the value to dump
     * @param int $depth how deep the dump descends into arrays and objects
     * @param bool|null $highlight whether to print highlighted HTML; null highlights everywhere but the command line
     * @return mixed the value, unchanged
     */
    function hdump(mixed $var, int $depth = 10, ?bool $highlight = null): mixed
    {
        $highlight ??= PHP_SAPI !== 'cli';

        try {
            $dumper = ObjectManager::getInstance()->get(VarDumperInterface::class);
        } catch (\RuntimeException) {
            $dumper = null;
        }

        if ($dumper instanceof VarDumperInterface) {
            $text = $dumper->dumpAsString($var, $depth, $highlight);
        } else {
            $text = print_r($var, true);
            if ($highlight) {
                $text = '<pre>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
            }
        }

        echo $text . PHP_EOL;

        return $var;
    }
}

if (!function_exists('hdd')) {
    /**
     * Prints Base's dump of a value, as `hdump()` does, and stops the script.
     *
     * A web response gets the status 500 first, so a page cache does not store the page with the dump in it.
     *
     * @param mixed $var the value to dump
     * @param int $depth how deep the dump descends into arrays and objects
     * @param bool|null $highlight whether to print highlighted HTML; null highlights everywhere but the command line
     * @return never
     */
    function hdd(mixed $var, int $depth = 10, ?bool $highlight = null): never
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
        }

        hdump($var, $depth, $highlight);

        exit(1);
    }
}
