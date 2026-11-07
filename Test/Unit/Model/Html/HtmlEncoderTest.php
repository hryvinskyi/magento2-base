<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Model\Html\HtmlEncoder;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\HtmlEncoder
 */
class HtmlEncoderTest extends TestCase
{
    /**
     * @var HtmlEncoder
     */
    private HtmlEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = (new HtmlCoreServices())->htmlEncoder();
    }

    public function testEncode(): void
    {
        self::assertSame('a&lt;&gt;&amp;&quot;&#039;�', $this->encoder->encode("a<>&\"'\x80"));
        self::assertSame('Sam &amp; Dark', $this->encoder->encode('Sam & Dark'));
    }

    public function testEncodeEncodesExistingEntitiesAgain(): void
    {
        self::assertSame('&amp;lt; &amp;eacute;', $this->encoder->encode('&lt; &eacute;'));
    }

    /**
     * Encoding takes every value that has a text form, each cast to a string first.
     *
     * @param string $expected the encoded text
     * @param string|int|float|bool|\Stringable|null $content the value to encode
     * @return void
     */
    #[DataProvider('textLikeValueProvider')]
    public function testEncodeTakesEveryTextLikeValue(
        string $expected,
        string|int|float|bool|\Stringable|null $content
    ): void {
        self::assertSame($expected, $this->encoder->encode($content));
    }

    /**
     * Encoding that keeps entities takes the same values as encode().
     *
     * @param string $expected the encoded text
     * @param string|int|float|bool|\Stringable|null $content the value to encode
     * @return void
     */
    #[DataProvider('textLikeValueProvider')]
    public function testEncodeKeepingEntitiesTakesEveryTextLikeValue(
        string $expected,
        string|int|float|bool|\Stringable|null $content
    ): void {
        self::assertSame($expected, $this->encoder->encodeKeepingEntities($content));
    }

    /**
     * Values with a text form and their encoding.
     *
     * @return array<string, array{string, string|int|float|bool|\Stringable|null}>
     */
    public static function textLikeValueProvider(): array
    {
        return [
            'a string' => ['&lt;b&gt;', '<b>'],
            'null is empty' => ['', null],
            'an integer' => ['5', 5],
            'zero' => ['0', 0],
            'a float' => ['1.5', 1.5],
            'true' => ['1', true],
            'false is empty' => ['', false],
            'a stringable' => ['&lt;b&gt;', new class implements \Stringable {
                /**
                 * @inheritDoc
                 */
                public function __toString(): string
                {
                    return '<b>';
                }
            }],
        ];
    }

    public function testDecode(): void
    {
        self::assertSame("a<>&\"'", $this->encoder->decode('a&lt;&gt;&amp;&quot;&#039;'));
    }

    /**
     * @param string $expected
     * @param string $content
     */
    #[DataProvider('entityKeepingProvider')]
    public function testEncodeKeepingEntities(string $expected, string $content): void
    {
        self::assertSame($expected, $this->encoder->encodeKeepingEntities($content));
    }

    /**
     * Content and its encoding with existing entities kept.
     *
     * @return array<string, array{string, string}>
     */
    public static function entityKeepingProvider(): array
    {
        return [
            'an encoded tag is kept' => ['&lt;b&gt;', '&lt;b&gt;'],
            'a bare tag is encoded' => ['&lt;b&gt;', '<b>'],
            'a named entity is kept' => ['caf&eacute;', 'caf&eacute;'],
            'a mix of both' => ['&lt;p&gt; &amp; &lt;br&gt; &quot;&#039;', '&lt;p&gt; & <br> "\''],
            'zero is unchanged' => ['0', '0'],
            'empty is unchanged' => ['', ''],
            'invalid UTF-8 is substituted' => ['a�', "a\x80"],
        ];
    }
}
