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

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Reads a closure's source text from the file that defines it, from the `function` keyword to its closing brace.
 *
 * A closure whose source cannot be read (an internal function, a file that cannot be opened) yields a placeholder
 * closure whose body says so, so the result is always a PHP expression. Arrow functions (`fn`) are not recognised
 * and yield an empty string.
 */
class ClosureSourceReader
{
    /**
     * Text returned when the closure's source cannot be read.
     */
    private const UNREADABLE = 'function() {/* Error: unable to determine Closure source */}';

    /**
     * @param \ReflectionFunctionFactory $reflectionFunctionFactory creates the reflection of a closure
     * @param DriverInterface $driver reads the file that defines the closure
     */
    public function __construct(
        private readonly \ReflectionFunctionFactory $reflectionFunctionFactory,
        private readonly DriverInterface $driver
    ) {
    }

    /**
     * Returns the closure's source text.
     *
     * @param \Closure $closure the closure to read
     * @return string the source, or a placeholder closure when it cannot be read
     */
    public function read(\Closure $closure): string
    {
        $reflection = $this->reflectionFunctionFactory->create(['function' => $closure]);
        $fileName = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();
        $lines = $fileName === false ? false : $this->readLines($fileName);
        if ($lines === false || $start === false || $end === false) {
            return self::UNREADABLE;
        }

        $source = implode('', array_slice($lines, $start - 1, $end - $start + 1));

        return $this->extractClosure($source);
    }

    /**
     * Returns the lines of a file, each with its line terminator, the way `file()` splits it.
     *
     * The terminators are kept because the source is joined back with nothing in between.
     *
     * @param string $fileName the file to read
     * @return list<string>|false the lines, or false when the file cannot be read
     */
    private function readLines(string $fileName): array|false
    {
        try {
            $contents = $this->driver->fileGetContents($fileName);
        } catch (FileSystemException) {
            return false;
        }

        return preg_split('/(?<=\n)/', $contents, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns the part of the source from the first `function` keyword to the brace that closes its body.
     *
     * @param string $source the source lines that define the closure
     * @return string the closure's text
     */
    private function extractClosure(string $source): string
    {
        $tokens = \PhpToken::tokenize('<?php ' . $source);
        array_shift($tokens);

        $closureTokens = [];
        $openBraces = 0;
        foreach ($tokens as $token) {
            if ($token->id === T_FUNCTION) {
                $closureTokens[] = $token->text;
                continue;
            }
            if ($closureTokens === []) {
                continue;
            }

            $closureTokens[] = $token->text;
            $braceChange = match ($token->id) {
                ord('{') => 1,
                ord('}') => -1,
                default => 0,
            };
            $openBraces += $braceChange;
            if ($braceChange < 0 && $openBraces === 0) {
                break;
            }
        }

        return implode('', $closureTokens);
    }
}
