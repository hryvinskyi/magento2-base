<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\OptionReader;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\OptionReader
 */
class OptionReaderTest extends TestCase
{
    /**
     * @var OptionReader
     */
    private OptionReader $reader;

    protected function setUp(): void
    {
        $this->reader = (new HtmlElementServices())->optionReader();
    }

    public function testValueReturnsThePresentValue(): void
    {
        self::assertSame('a', $this->reader->value(['key' => 'a'], 'key', 'default'));
    }

    public function testValueReturnsTheDefaultForAnAbsentKey(): void
    {
        self::assertSame('default', $this->reader->value([], 'key', 'default'));
    }

    public function testAKeyPresentWithNullYieldsNullRatherThanTheDefault(): void
    {
        self::assertNull($this->reader->value(['key' => null], 'key', 'default'));
    }

    public function testValueTakesTheKeyLiterally(): void
    {
        self::assertSame('default', $this->reader->value(['a' => ['b' => 1]], 'a.b', 'default'));
    }

    public function testArrayValueReturnsAnArrayAndAnEmptyOneForAnythingElse(): void
    {
        self::assertSame(['a'], $this->reader->arrayValue(['key' => ['a']], 'key'));
        self::assertSame(['a'], $this->reader->arrayValue([5 => ['a']], 5));
        self::assertSame([], $this->reader->arrayValue(['key' => 'a'], 'key'));
        self::assertSame([], $this->reader->arrayValue(['key' => null], 'key'));
        self::assertSame([], $this->reader->arrayValue([], 'key'));
    }

    public function testTagNameIsTheDefaultForAnAbsentKey(): void
    {
        self::assertSame('div', $this->reader->tagName([], 'div'));
    }

    public function testTagNameIsNullForNullAndFalse(): void
    {
        self::assertNull($this->reader->tagName(['tag' => null], 'div'));
        self::assertNull($this->reader->tagName(['tag' => false], 'div'));
    }

    public function testTagNameIsTheTextOfAnyOtherValue(): void
    {
        self::assertSame('ul', $this->reader->tagName(['tag' => 'ul'], 'div'));
        self::assertSame('5', $this->reader->tagName(['tag' => 5], 'div'));
        self::assertSame('1', $this->reader->tagName(['tag' => true], 'div'));
    }

    public function testTagNameRefusesAValueWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->reader->tagName(['tag' => ['div']], 'div');
    }

    public function testItemFormatterIsNullWhenAbsentOrNull(): void
    {
        self::assertNull($this->reader->itemFormatter([]));
        self::assertNull($this->reader->itemFormatter(['item' => null]));
    }

    public function testItemFormatterReturnsACallable(): void
    {
        $callback = static function (): string {
            return 'a';
        };

        self::assertSame($callback, $this->reader->itemFormatter(['item' => $callback]));
    }

    public function testItemFormatterRefusesAnythingElse(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->reader->itemFormatter(['item' => 'not a callable']);
    }
}
