<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\AttributeRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\AttributeRenderer
 */
class AttributeRendererTest extends TestCase
{
    /**
     * @var AttributeRenderer
     */
    private AttributeRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlCoreServices())->attributeRenderer();
    }

    public function testRenderAttributes(): void
    {
        self::assertSame('', $this->renderer->render([]));
        self::assertSame(
            ' name="test" value="1&lt;&gt;"',
            $this->renderer->render(['name' => 'test', 'empty' => null, 'value' => '1<>'])
        );
        self::assertSame(
            ' checked disabled',
            $this->renderer->render(['checked' => true, 'disabled' => true, 'hidden' => false])
        );
        self::assertSame(' class="first second"', $this->renderer->render(['class' => ['first', 'second']]));
        self::assertSame('', $this->renderer->render(['class' => []]));
        self::assertSame(
            ' style="width: 100px; height: 200px;"',
            $this->renderer->render(['style' => ['width' => '100px', 'height' => '200px']])
        );
        self::assertSame('', $this->renderer->render(['style' => []]));
        self::assertSame(' data-foo=\'[]\'', $this->renderer->render(['data' => ['foo' => []]]));
    }

    public function testAttributesFollowThePreferredOrderAndTheRestKeepTheirs(): void
    {
        self::assertSame(
            ' type="text" id="i" class="c" name="n" title="t" data-x="1" onclick="go()"',
            $this->renderer->render([
                'data' => ['x' => 1],
                'title' => 't',
                'onclick' => 'go()',
                'name' => 'n',
                'class' => 'c',
                'id' => 'i',
                'type' => 'text',
            ])
        );
    }

    public function testANullAttributeIsNotMovedNorRendered(): void
    {
        self::assertSame(' id="i" title="t"', $this->renderer->render(['title' => 't', 'id' => 'i', 'type' => null]));
    }

    public function testDataAttributeElements(): void
    {
        self::assertSame(
            ' data-on data-n="1" data-list=\'["a","\u003Cb\u003E"]\' aria-label="x &amp; y"',
            $this->renderer->render([
                'data' => ['on' => true, 'off' => false, 'none' => null, 'n' => 1, 'list' => ['a', '<b>']],
                'aria' => ['label' => 'x & y'],
            ])
        );
    }

    public function testAnArrayUnderAnyOtherNameIsHtmlSafeJson(): void
    {
        self::assertSame(
            ' options=\'{"q":"\u0022\u0026\u0027"}\'',
            $this->renderer->render(['options' => ['q' => '"&\'']])
        );
    }

    public function testClassArrayDropsDuplicatesAndEmptyClasses(): void
    {
        self::assertSame(' class="a b"', $this->renderer->render(['class' => ['a', 'a b', '']]));
        self::assertSame(' class="a&quot;"', $this->renderer->render(['class' => ['a"']]));
    }

    public function testClassArrayKeepsDuplicatesWhenNormalisationIsOff(): void
    {
        $renderer = (new HtmlCoreServices())->attributeRenderer(normalizeClassAttribute: false);

        self::assertSame(' class="a a b"', $renderer->render(['class' => ['a', 'a b', '']]));
    }

    public function testConfiguredAttributeOrderReplacesTheDefault(): void
    {
        $renderer = (new HtmlCoreServices())->attributeRenderer(attributeOrder: ['first' => 'title', 'second' => 'id']);

        self::assertSame(
            ' title="t" id="i" type="x"',
            $renderer->render(['type' => 'x', 'id' => 'i', 'title' => 't'])
        );
    }

    public function testConfiguredDataAttributesReplaceTheDefault(): void
    {
        $renderer = (new HtmlCoreServices())->attributeRenderer(dataAttributes: ['x' => 'v']);

        self::assertSame(
            ' v-a="1" data=\'{"b":2}\'',
            $renderer->render(['v' => ['a' => 1], 'data' => ['b' => 2]])
        );
    }

    public function testDataAttributeNamesMatchLoosely(): void
    {
        $renderer = (new HtmlCoreServices())->attributeRenderer(dataAttributes: ['7']);

        self::assertSame(' 7-a="1"', $renderer->render([7 => ['a' => 1]]));
    }

    public function testStringableValuesAreRenderedAsText(): void
    {
        $value = new class () implements \Stringable {
            /**
             * @return string
             */
            public function __toString(): string
            {
                return '<v>';
            }
        };

        self::assertSame(' title="&lt;v&gt;" data-v="&lt;v&gt;"', $this->renderer->render([
            'title' => $value,
            'data' => ['v' => $value],
        ]));
    }

    public function testAValueWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);

        $this->renderer->render(['title' => new \stdClass()]);
    }

    public function testADataElementWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);

        $this->renderer->render(['data' => ['x' => new \stdClass()]]);
    }
}
