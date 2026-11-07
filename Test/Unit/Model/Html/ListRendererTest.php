<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\ListRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlTraversableSelection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\ListRenderer
 */
class ListRendererTest extends TestCase
{
    /**
     * @var ListRenderer
     */
    private ListRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlElementServices())->listRenderer();
    }

    public function testUl(): void
    {
        $data = [
            1, 'abc', '<>',
        ];
        $expected = <<<'EOD'
<ul>
<li>1</li>
<li>abc</li>
<li>&lt;&gt;</li>
</ul>
EOD;
        $this->assertSameLines($expected, $this->renderer->ul($data));
        $expected = <<<'EOD'
<ul class="test">
<li class="item-0">1</li>
<li class="item-1">abc</li>
<li class="item-2"><></li>
</ul>
EOD;
        $this->assertSameLines($expected, $this->renderer->ul($data, [
            'class' => 'test',
            'item' => $this->listItemFormatter(),
        ]));
        self::assertSame('<ul class="test"></ul>', $this->renderer->ul([], ['class' => 'test']));
        self::assertStringMatchesFormat('<foo>%A</foo>', $this->renderer->ul([], ['tag' => 'foo']));
    }

    public function testUlRejectsAnItemThatCannotBeRenderedAsText(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->ul([['nested']]);
    }

    public function testOl(): void
    {
        $data = [
            1, 'abc', '<>',
        ];
        $expected = <<<'EOD'
<ol>
<li class="ti">1</li>
<li class="ti">abc</li>
<li class="ti">&lt;&gt;</li>
</ol>
EOD;
        $this->assertSameLines($expected, $this->renderer->ol($data, [
            'itemOptions' => ['class' => 'ti'],
        ]));
        $expected = <<<'EOD'
<ol class="test">
<li class="item-0">1</li>
<li class="item-1">abc</li>
<li class="item-2"><></li>
</ol>
EOD;
        $this->assertSameLines($expected, $this->renderer->ol($data, [
            'class' => 'test',
            'item' => $this->listItemFormatter(),
        ]));
        self::assertSame('<ol class="test"></ol>', $this->renderer->ol([], ['class' => 'test']));
    }

    public function testUlWithANullOrFalseTagRendersNoListTag(): void
    {
        foreach ([null, false] as $tag) {
            self::assertSame(
                "\n<li>a</li>\n<li>b</li>\n",
                $this->renderer->ul(['a', 'b'], ['tag' => $tag, 'class' => 'ignored'])
            );
            self::assertSame('', $this->renderer->ul([], ['tag' => $tag]));
        }
    }

    public function testUlWithATagRendersItWithItsAttributes(): void
    {
        self::assertSame(
            "<div class=\"c\">\n<li>a</li>\n</div>",
            $this->renderer->ul(['a'], ['tag' => 'div', 'class' => 'c'])
        );
    }

    public function testOlIgnoresATagOption(): void
    {
        self::assertSame("<ol>\n<li>a</li>\n</ol>", $this->renderer->ol(['a'], ['tag' => 'ul']));
    }

    public function testUlWithEncodeOffPrintsTheItemsAsGiven(): void
    {
        self::assertSame("<ul>\n<li><b></li>\n</ul>", $this->renderer->ul(['<b>'], ['encode' => false]));
    }

    public function testAnEncodeOptionSetToNullIsOffRatherThanTheDefault(): void
    {
        self::assertSame("<ul>\n<li><b></li>\n</ul>", $this->renderer->ul(['<b>'], ['encode' => null]));
    }

    public function testUlAppliesItemOptionsAndTheSeparator(): void
    {
        self::assertSame(
            '<ul>|<li class="i">a</li>|<li class="i">b</li>|</ul>',
            $this->renderer->ul(['a', 'b'], ['separator' => '|', 'itemOptions' => ['class' => 'i']])
        );
    }

    public function testASeparatorSetToNullIsEmptyRatherThanTheDefault(): void
    {
        self::assertSame(
            '<ul><li>a</li><li>b</li></ul>',
            $this->renderer->ul(['a', 'b'], ['separator' => null])
        );
    }

    public function testUlTakesATraversable(): void
    {
        self::assertSame(
            "<ul>\n<li>a</li>\n</ul>",
            $this->renderer->ul(new HtmlTraversableSelection(['a']))
        );
    }

    public function testTheItemCallbackGetsTheKeyOfTheItem(): void
    {
        self::assertSame(
            "<ul>\nfirst=a\n</ul>",
            $this->renderer->ul(['first' => 'a'], [
                'item' => static function (string $item, int|string $index): string {
                    return $index . '=' . $item;
                },
            ])
        );
    }

    public function testUlRejectsAnItemOptionThatIsNotCallable(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->ul(['a'], ['item' => 'not a callable']);
    }

    public function testUlRejectsATagWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->ul(['a'], ['tag' => ['ul']]);
    }

    /**
     * An `item` callback for ul() and ol() that prints a list item with an index class.
     *
     * @return \Closure(int|string, int): string
     */
    private function listItemFormatter(): \Closure
    {
        return function (int|string $item, int $index): string {
            return "<li class=\"item-$index\">$item</li>";
        };
    }

    /**
     * Compares two strings with their line endings normalised to "\n".
     *
     * @param string $expected
     * @param string $actual
     */
    private function assertSameLines(string $expected, string $actual): void
    {
        $this->assertSame(str_replace("\r\n", "\n", $expected), str_replace("\r\n", "\n", $actual));
    }
}
