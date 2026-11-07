<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Model\Html\JsRegexConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\JsRegexConverter
 */
class JsRegexConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $converter = new JsRegexConverter();

        self::assertSame('/[a-z0-9-]+/', $converter->convert('([a-z0-9-]+)'));
        self::assertSame('/([a-z0-9-]+)/gim', $converter->convert('/([a-z0-9-]+)/Ugimex'));
    }

    public function testHexEscapesBecomeUnicodeEscapes(): void
    {
        $converter = new JsRegexConverter();

        self::assertSame('/\\u0041/', $converter->convert('/\\x0041/'));
        self::assertSame('/\\u41/', $converter->convert('/\\x{41}/'));
    }

    public function testAnotherDelimiterBecomesASlashAndInnerSlashesAreEscaped(): void
    {
        self::assertSame('/a\\/b/i', (new JsRegexConverter())->convert('#a/b#i'));
    }
}
