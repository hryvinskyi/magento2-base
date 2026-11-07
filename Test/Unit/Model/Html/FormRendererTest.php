<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\FormRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\FormRenderer
 */
class FormRendererTest extends TestCase
{
    /**
     * @var FormRenderer
     */
    private FormRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlElementServices())->formRenderer();
    }

    /**
     * @param string $expected
     * @param string $method
     */
    #[DataProvider('dataProviderBeginFormMethod')]
    public function testBeginFormPrintsTheMethodAsGiven(string $expected, string $method): void
    {
        $this->assertSame($expected, $this->renderer->beginForm('/foo', $method));
    }

    /**
     * Data provider for testBeginFormPrintsTheMethodAsGiven().
     *
     * @return list<array{string, string}> test data
     */
    public static function dataProviderBeginFormMethod(): array
    {
        return [
            ['<form action="/foo" method="GET">', 'GET'],
            ['<form action="/foo" method="POST">', 'POST'],
            ['<form action="/foo" method="DELETE">', 'DELETE'],
            ['<form action="/foo" method="post">', 'post'],
        ];
    }

    public function testBeginForm(): void
    {
        self::assertSame('<form action="" method="post">', $this->renderer->beginForm());
        self::assertSame('<form action="/example" method="get">', $this->renderer->beginForm('/example', 'get'));
        $hiddens = [
            '<input type="hidden" name="id" value="1" />',
            '<input type="hidden" name="title" value="&lt;" />',
        ];
        self::assertSame(
            '<form action="/example" method="get">' . "\n" . implode("\n", $hiddens),
            $this->renderer->beginForm('/example?id=1&title=%3C', 'get')
        );
        $expected = '<form action="/foo" method="GET">%A<input type="hidden" name="p" value="" />';
        $actual = $this->renderer->beginForm('/foo?p', 'GET');
        self::assertStringMatchesFormat($expected, $actual);
    }

    public function testBeginFormAddsTheFormKey(): void
    {
        $this->assertSame(
            '<form action="/save" method="post">' . "\n" . '<input type="hidden" name="form_key" value="k&lt;1" />',
            $this->renderer->beginForm('/save', 'post', 'k<1')
        );
    }

    public function testEndForm(): void
    {
        self::assertSame('</form>', $this->renderer->endForm());
    }

    public function testLabel(): void
    {
        self::assertSame('<label>something<></label>', $this->renderer->label('something<>'));
        self::assertSame('<label for="a">something<></label>', $this->renderer->label('something<>', 'a'));
        self::assertSame(
            '<label class="test" for="a">something<></label>',
            $this->renderer->label('something<>', 'a', ['class' => 'test'])
        );
    }

    public function testButton(): void
    {
        self::assertSame('<button type="button">Button</button>', $this->renderer->button());
        self::assertSame(
            '<button type="button" name="test" value="value">content<></button>',
            $this->renderer->button('content<>', ['name' => 'test', 'value' => 'value'])
        );
        self::assertSame(
            '<button type="submit" class="t" name="test" value="value">content<></button>',
            $this->renderer->button(
                'content<>',
                ['type' => 'submit', 'name' => 'test', 'value' => 'value', 'class' => 't']
            )
        );
    }

    public function testSubmitButton(): void
    {
        self::assertSame('<button type="submit">Submit</button>', $this->renderer->submitButton());
        self::assertSame(
            '<button type="submit" class="t" name="test" value="value">content<></button>',
            $this->renderer->submitButton('content<>', ['name' => 'test', 'value' => 'value', 'class' => 't'])
        );
    }

    public function testResetButton(): void
    {
        self::assertSame('<button type="reset">Reset</button>', $this->renderer->resetButton());
        self::assertSame(
            '<button type="reset" class="t" name="test" value="value">content<></button>',
            $this->renderer->resetButton('content<>', ['name' => 'test', 'value' => 'value', 'class' => 't'])
        );
    }

    /**
     * Data provider for testTextarea().
     *
     * @return list<array{string, string, string|null, array<string, mixed>}>
     */
    public static function textareaDataProvider(): array
    {
        return [
            [
                '<textarea name="test"></textarea>',
                'test',
                null,
                [],
            ],
            [
                '<textarea class="t" name="test">value&lt;&gt;</textarea>',
                'test',
                'value<>',
                ['class' => 't'],
            ],
            [
                '<textarea name="test">value&amp;lt;&amp;gt;</textarea>',
                'test',
                'value&lt;&gt;',
                [],
            ],
            [
                '<textarea name="test">value&lt;&gt;</textarea>',
                'test',
                'value&lt;&gt;',
                ['doubleEncode' => false],
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $name
     * @param string|null $value
     * @param array<string, mixed> $options
     */
    #[DataProvider('textareaDataProvider')]
    public function testTextarea(string $expected, string $name, ?string $value, array $options): void
    {
        self::assertSame($expected, $this->renderer->textarea($name, $value, $options));
    }

    public function testBeginFormPassesOptionsAsAttributes(): void
    {
        self::assertSame(
            '<form class="c" action="/save" method="post" data-role="f">',
            $this->renderer->beginForm('/save', 'post', null, ['class' => 'c', 'data' => ['role' => 'f']])
        );
    }

    public function testBeginFormKeepsTheQueryOfANonGetAction(): void
    {
        self::assertSame(
            '<form action="/save?a=1" method="post">',
            $this->renderer->beginForm('/save?a=1')
        );
    }

    public function testBeginFormPrintsTheFormKeyBeforeTheQueryParameters(): void
    {
        self::assertSame(
            '<form action="/find" method="GET">' . "\n"
                . '<input type="hidden" name="form_key" value="k" />' . "\n"
                . '<input type="hidden" name="q" value="a=b c" />',
            $this->renderer->beginForm('/find?q=a%3Db+c', 'GET', 'k')
        );
    }

    public function testLabelForArgumentReplacesTheOption(): void
    {
        self::assertSame('<label>text</label>', $this->renderer->label('text', null, ['for' => 'other']));
    }

    public function testSubmitAndResetButtonsForceTheirType(): void
    {
        self::assertSame('<button type="submit">s</button>', $this->renderer->submitButton('s', ['type' => 'button']));
        self::assertSame('<button type="reset">r</button>', $this->renderer->resetButton('r', ['type' => 'button']));
    }

    public function testTextareaDoubleEncodesWhenTheOptionIsTrue(): void
    {
        self::assertSame(
            '<textarea name="test">value&amp;lt;</textarea>',
            $this->renderer->textarea('test', 'value&lt;', ['doubleEncode' => true])
        );
    }

    public function testTextareaEncodesABareCharacterWhenItKeepsEntities(): void
    {
        self::assertSame(
            '<textarea name="test">a&lt;b &eacute;</textarea>',
            $this->renderer->textarea('test', 'a<b &eacute;', ['doubleEncode' => false])
        );
    }

    public function testADoubleEncodeOptionSetToNullIsOffRatherThanTheDefault(): void
    {
        self::assertSame(
            '<textarea name="test">value&lt;</textarea>',
            $this->renderer->textarea('test', 'value&lt;', ['doubleEncode' => null])
        );
    }

    public function testTextareaCastsItsValueToAString(): void
    {
        self::assertSame('<textarea name="test">5</textarea>', $this->renderer->textarea('test', 5));
    }

    public function testButtonRefusesAnAttributeWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->button('b', ['title' => new \stdClass()]);
    }
}
