<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\LinkRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\LinkRenderer
 */
class LinkRendererTest extends TestCase
{
    /**
     * @var LinkRenderer
     */
    private LinkRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlElementServices())->linkRenderer();
    }

    public function testA(): void
    {
        self::assertSame('<a>something<></a>', $this->renderer->a('something<>'));
        self::assertSame('<a href="/example">something</a>', $this->renderer->a('something', '/example'));
        self::assertSame('<a href="">something</a>', $this->renderer->a('something', ''));
        self::assertSame(
            '<a href="http://www.быстроном.рф">http://www.быстроном.рф</a>',
            $this->renderer->a('http://www.быстроном.рф', 'http://www.быстроном.рф')
        );
    }

    public function testMailto(): void
    {
        self::assertSame('<a href="mailto:test&lt;&gt;">test<></a>', $this->renderer->mailto('test<>'));
        self::assertSame('<a href="mailto:test&gt;">test<></a>', $this->renderer->mailto('test<>', 'test>'));
    }

    /**
     * Data provider for testImg().
     *
     * @return list<array{string, string, array<string, mixed>}>
     */
    public static function imgDataProvider(): array
    {
        return [
            [
                '<img src="/example" alt="" />',
                '/example',
                [],
            ],
            [
                '<img src="/test" alt="" />',
                '/test',
                [],
            ],
            [
                '<img src="/example" width="10" alt="something" />',
                '/example',
                [
                    'alt' => 'something',
                    'width' => 10,
                ],
            ],
            [
                '<img src="/base-url" srcset="" alt="" />',
                '/base-url',
                [
                    'srcset' => [
                    ],
                ],
            ],
            [
                '<img src="/base-url" srcset="/example-9001w 9001w" alt="" />',
                '/base-url',
                [
                    'srcset' => [
                        '9001w' => '/example-9001w',
                    ],
                ],
            ],
            [
                '<img src="/base-url" srcset="/example-100w 100w,/example-500w 500w,/example-1500w 1500w" alt="" />',
                '/base-url',
                [
                    'srcset' => [
                        '100w' => '/example-100w',
                        '500w' => '/example-500w',
                        '1500w' => '/example-1500w',
                    ],
                ],
            ],
            [
                '<img src="/base-url" srcset="/example-1x 1x,/example-2x 2x,/example-3x 3x,/example-4x 4x,'
                    . '/example-5x 5x" alt="" />',
                '/base-url',
                [
                    'srcset' => [
                        '1x' => '/example-1x',
                        '2x' => '/example-2x',
                        '3x' => '/example-3x',
                        '4x' => '/example-4x',
                        '5x' => '/example-5x',
                    ],
                ],
            ],
            [
                '<img src="/base-url" srcset="/example-1.42x 1.42x,/example-2.0x 2.0x,'
                    . '/example-3.99999x 3.99999x" alt="" />',
                '/base-url',
                [
                    'srcset' => [
                        '1.42x' => '/example-1.42x',
                        '2.0x' => '/example-2.0x',
                        '3.99999x' => '/example-3.99999x',
                    ],
                ],
            ],
            [
                '<img src="/base-url" srcset="/example-1x 1x,/example-2x 2x,/example-3x 3x" alt="" />',
                '/base-url',
                [
                    'srcset' => '/example-1x 1x,/example-2x 2x,/example-3x 3x',
                ],
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $src
     * @param array<string, mixed> $options
     */
    #[DataProvider('imgDataProvider')]
    public function testImg(string $expected, string $src, array $options): void
    {
        self::assertSame($expected, $this->renderer->img($src, $options));
    }

    public function testImgKeepsAGivenAlt(): void
    {
        self::assertSame('<img src="/a" alt="b" />', $this->renderer->img('/a', ['alt' => 'b']));
    }

    public function testImgRefusesASrcsetUrlWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->img('/a', ['srcset' => ['2x' => new \stdClass()]]);
    }
}
