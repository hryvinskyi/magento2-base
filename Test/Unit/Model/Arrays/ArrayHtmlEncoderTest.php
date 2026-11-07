<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayHtmlEncoder;
use Magento\Framework\ZendEscaper;
use PHPUnit\Framework\TestCase;

class ArrayHtmlEncoderTest extends TestCase
{
    /**
     * @var ArrayHtmlEncoder the service under test, built with its real collaborators
     */
    private ArrayHtmlEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new ArrayHtmlEncoder(new ZendEscaper());
    }

    public function testHtmlEncode(): void
    {
        $array = [
            'abc' => '123',
            '<' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
            'invalid' => "a\x80b",
        ];
        $this->assertEquals([
            'abc' => '123',
            '<' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a&lt;&gt;b',
                '23' => true,
            ],
            'invalid' => 'a�b',
        ], $this->encoder->htmlEncode($array));
        $this->assertEquals([
            'abc' => '123',
            '&lt;' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '&lt;&gt;' => 'a&lt;&gt;b',
                '23' => true,
            ],
            'invalid' => 'a�b',
        ], $this->encoder->htmlEncode($array, false));
    }

    public function testHtmlEncodeEncodesQuotesAndExistingEntities(): void
    {
        $this->assertSame(
            ['&quot;&#039;&amp;amp;'],
            $this->encoder->htmlEncode(['"\'&amp;'])
        );
    }

    public function testHtmlDecode(): void
    {
        $array = [
            'abc' => '123',
            '&lt;' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a&lt;&gt;b',
                '23' => true,
            ],
        ];
        $this->assertEquals([
            'abc' => '123',
            '&lt;' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
        ], $this->encoder->htmlDecode($array));
        $this->assertEquals([
            'abc' => '123',
            '<' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
        ], $this->encoder->htmlDecode($array, false));
    }
}
