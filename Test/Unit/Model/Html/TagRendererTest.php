<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\TagRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\TagRenderer
 */
class TagRendererTest extends TestCase
{
    /**
     * @var TagRenderer
     */
    private TagRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlCoreServices())->tagRenderer();
    }

    public function testTag(): void
    {
        self::assertSame('<br />', $this->renderer->tag('br'));
        self::assertSame('<span></span>', $this->renderer->tag('span'));
        self::assertSame('<div>content</div>', $this->renderer->tag('div', 'content'));
        self::assertSame(
            '<input type="text" name="test" value="&lt;&gt;" />',
            $this->renderer->tag('input', '', ['type' => 'text', 'name' => 'test', 'value' => '<>'])
        );
        self::assertSame('<span disabled></span>', $this->renderer->tag('span', '', ['disabled' => true]));
        self::assertSame('test', $this->renderer->tag(null, 'test'));
    }

    public function testVoidElementsMatchCaseInsensitivelyAndDropTheContent(): void
    {
        self::assertSame('<BR />', $this->renderer->tag('BR', 'ignored'));
        self::assertSame('<img src="a.png" />', $this->renderer->tag('img', 'ignored', ['src' => 'a.png']));
    }

    public function testContentIsPrintedAsGiven(): void
    {
        self::assertSame('<p><b>a & b</b></p>', $this->renderer->tag('p', '<b>a & b</b>'));
    }

    public function testBeginTag(): void
    {
        self::assertSame('<br>', $this->renderer->beginTag('br'));
        self::assertSame(
            '<span id="test" class="title">',
            $this->renderer->beginTag('span', ['id' => 'test', 'class' => 'title'])
        );
        self::assertSame('', $this->renderer->beginTag(null));
    }

    public function testEndTag(): void
    {
        self::assertSame('</br>', $this->renderer->endTag('br'));
        self::assertSame('</span>', $this->renderer->endTag('span'));
        self::assertSame('', $this->renderer->endTag(null));
    }

    public function testBooleanAttributes(): void
    {
        $input = ['type' => 'email', 'name' => 'mail', 'value' => null];

        self::assertSame(
            '<input type="email" name="mail" />',
            $this->renderer->tag('input', '', $input + ['required' => false])
        );
        self::assertSame(
            '<input type="email" name="mail" required />',
            $this->renderer->tag('input', '', $input + ['required' => true])
        );
        self::assertSame(
            '<input type="email" name="mail" required="hi" />',
            $this->renderer->tag('input', '', $input + ['required' => 'hi'])
        );
    }

    public function testDataAttributes(): void
    {
        self::assertSame(
            '<link src="xyz" data-a="1" data-b="c" />',
            $this->renderer->tag('link', '', ['src' => 'xyz', 'data' => ['a' => 1, 'b' => 'c']])
        );
        self::assertSame(
            '<link src="xyz" ng-a="1" ng-b="c" />',
            $this->renderer->tag('link', '', ['src' => 'xyz', 'ng' => ['a' => 1, 'b' => 'c']])
        );
        self::assertSame(
            '<link src="xyz" data-ng-a="1" data-ng-b="c" />',
            $this->renderer->tag('link', '', ['src' => 'xyz', 'data-ng' => ['a' => 1, 'b' => 'c']])
        );
        self::assertSame(
            '<link src=\'{"a":1,"b":"It\\u0027s"}\' />',
            $this->renderer->tag('link', '', ['src' => ['a' => 1, 'b' => "It's"]])
        );
    }

    public function testConfiguredVoidElementsReplaceTheDefaults(): void
    {
        $renderer = (new HtmlCoreServices())->tagRenderer(['custom' => true, 'br' => false]);

        self::assertSame('<custom />', $renderer->tag('custom'));
        self::assertSame('<br></br>', $renderer->tag('br'));
        self::assertSame('<img></img>', $renderer->tag('img'));
    }

    public function testAnAttributeWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);

        $this->renderer->tag('span', '', ['title' => new \stdClass()]);
    }
}
