<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers;

use Hryvinskyi\Base\Helper\Html;
use Hryvinskyi\Base\Helper\InvalidParamException;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\HtmlTraversableSelection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testEncode(): void
    {
        $this->assertEquals('a&lt;&gt;&amp;&quot;&#039;�', Html::encode("a<>&\"'\x80"));
        $this->assertEquals('Sam &amp; Dark', Html::encode('Sam & Dark'));
    }

    public function testDecode(): void
    {
        $this->assertEquals("a<>&\"'", Html::decode('a&lt;&gt;&amp;&quot;&#039;'));
    }

    public function testTag(): void
    {
        $this->assertEquals('<br />', Html::tag('br'));
        $this->assertEquals('<span></span>', Html::tag('span'));
        $this->assertEquals('<div>content</div>', Html::tag('div', 'content'));
        $this->assertEquals(
            '<input type="text" name="test" value="&lt;&gt;" />',
            Html::tag('input', '', ['type' => 'text', 'name' => 'test', 'value' => '<>'])
        );
        $this->assertEquals('<span disabled></span>', Html::tag('span', '', ['disabled' => true]));
        $this->assertEquals('test', Html::tag(false, 'test'));
        $this->assertEquals('test', Html::tag(null, 'test'));
    }

    public function testBeginTag(): void
    {
        $this->assertEquals('<br>', Html::beginTag('br'));
        $this->assertEquals(
            '<span id="test" class="title">',
            Html::beginTag('span', ['id' => 'test', 'class' => 'title'])
        );
        $this->assertEquals('', Html::beginTag(null));
        $this->assertEquals('', Html::beginTag(false));
    }

    public function testEndTag(): void
    {
        $this->assertEquals('</br>', Html::endTag('br'));
        $this->assertEquals('</span>', Html::endTag('span'));
        $this->assertEquals('', Html::endTag(null));
        $this->assertEquals('', Html::endTag(false));
    }

    public function testStyle(): void
    {
        $content = 'a <>';
        $this->assertEquals("<style>{$content}</style>", Html::style($content));
        $this->assertEquals(
            "<style type=\"text/less\">{$content}</style>",
            Html::style($content, ['type' => 'text/less'])
        );
    }

    public function testScript(): void
    {
        $content = 'a <>';
        $this->assertEquals("<script>{$content}</script>", Html::script($content));
        $this->assertEquals(
            "<script type=\"text/js\">{$content}</script>",
            Html::script($content, ['type' => 'text/js'])
        );
    }

    public function testCssFile(): void
    {
        $link = '<link href="http://example.com" rel="stylesheet" />';
        $this->assertEquals($link, Html::cssFile('http://example.com'));
        $this->assertEquals('<link href="/test" rel="stylesheet" />', Html::cssFile('/test'));
        $this->assertEquals(
            "<!--[if IE 9]>\n" . $link . "\n<![endif]-->",
            Html::cssFile('http://example.com', ['condition' => 'IE 9'])
        );
        $this->assertEquals(
            "<!--[if (gte IE 9)|(!IE)]><!-->\n" . $link . "\n<!--<![endif]-->",
            Html::cssFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)'])
        );
        $this->assertEquals(
            '<noscript>' . $link . '</noscript>',
            Html::cssFile('http://example.com', ['noscript' => true])
        );
    }

    public function testJsFile(): void
    {
        $script = '<script src="http://example.com"></script>';
        $this->assertEquals($script, Html::jsFile('http://example.com'));
        $this->assertEquals('<script src="/test"></script>', Html::jsFile('/test'));
        $this->assertEquals(
            "<!--[if IE 9]>\n" . $script . "\n<![endif]-->",
            Html::jsFile('http://example.com', ['condition' => 'IE 9'])
        );
        $this->assertEquals(
            "<!--[if (gte IE 9)|(!IE)]><!-->\n" . $script . "\n<!--<![endif]-->",
            Html::jsFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)'])
        );
    }

    /**
     * @param string $expected
     * @param string $method
     */
    #[DataProvider('dataProviderBeginFormMethod')]
    public function testBeginFormPrintsTheMethodAsGiven(string $expected, string $method): void
    {
        $this->assertSame($expected, Html::beginForm('/foo', $method));
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
        $this->assertEquals('<form action="" method="post">', Html::beginForm());
        $this->assertEquals('<form action="/example" method="get">', Html::beginForm('/example', 'get'));
        $hiddens = [
            '<input type="hidden" name="id" value="1" />',
            '<input type="hidden" name="title" value="&lt;" />',
        ];
        $this->assertEquals(
            '<form action="/example" method="get">' . "\n" . implode("\n", $hiddens),
            Html::beginForm('/example?id=1&title=%3C', 'get')
        );
        $expected = '<form action="/foo" method="GET">%A<input type="hidden" name="p" value="" />';
        $actual = Html::beginForm('/foo?p', 'GET');
        $this->assertStringMatchesFormat($expected, $actual);
    }

    public function testBeginFormAddsTheFormKey(): void
    {
        $this->assertSame(
            '<form action="/save" method="post">' . "\n" . '<input type="hidden" name="form_key" value="k&lt;1" />',
            Html::beginForm('/save', 'post', 'k<1')
        );
    }

    public function testEndForm(): void
    {
        $this->assertEquals('</form>', Html::endForm());
    }

    public function testA(): void
    {
        $this->assertEquals('<a>something<></a>', Html::a('something<>'));
        $this->assertEquals('<a href="/example">something</a>', Html::a('something', '/example'));
        $this->assertEquals('<a href="">something</a>', Html::a('something', ''));
        $this->assertEquals(
            '<a href="http://www.быстроном.рф">http://www.быстроном.рф</a>',
            Html::a('http://www.быстроном.рф', 'http://www.быстроном.рф')
        );
    }

    public function testMailto(): void
    {
        $this->assertEquals('<a href="mailto:test&lt;&gt;">test<></a>', Html::mailto('test<>'));
        $this->assertEquals('<a href="mailto:test&gt;">test<></a>', Html::mailto('test<>', 'test>'));
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
        $this->assertEquals($expected, Html::img($src, $options));
    }

    public function testLabel(): void
    {
        $this->assertEquals('<label>something<></label>', Html::label('something<>'));
        $this->assertEquals('<label for="a">something<></label>', Html::label('something<>', 'a'));
        $this->assertEquals(
            '<label class="test" for="a">something<></label>',
            Html::label('something<>', 'a', ['class' => 'test'])
        );
    }

    public function testButton(): void
    {
        $this->assertEquals('<button type="button">Button</button>', Html::button());
        $this->assertEquals(
            '<button type="button" name="test" value="value">content<></button>',
            Html::button('content<>', ['name' => 'test', 'value' => 'value'])
        );
        $this->assertEquals(
            '<button type="submit" class="t" name="test" value="value">content<></button>',
            Html::button('content<>', ['type' => 'submit', 'name' => 'test', 'value' => 'value', 'class' => 't'])
        );
    }

    public function testSubmitButton(): void
    {
        $this->assertEquals('<button type="submit">Submit</button>', Html::submitButton());
        $this->assertEquals(
            '<button type="submit" class="t" name="test" value="value">content<></button>',
            Html::submitButton('content<>', ['name' => 'test', 'value' => 'value', 'class' => 't'])
        );
    }

    public function testResetButton(): void
    {
        $this->assertEquals('<button type="reset">Reset</button>', Html::resetButton());
        $this->assertEquals(
            '<button type="reset" class="t" name="test" value="value">content<></button>',
            Html::resetButton('content<>', ['name' => 'test', 'value' => 'value', 'class' => 't'])
        );
    }

    public function testInput(): void
    {
        $this->assertEquals('<input type="text" />', Html::input('text'));
        $this->assertEquals(
            '<input type="text" class="t" name="test" value="value" />',
            Html::input('text', 'test', 'value', ['class' => 't'])
        );
    }

    public function testButtonInput(): void
    {
        $this->assertEquals('<input type="button" value="Button" />', Html::buttonInput());
        $this->assertEquals(
            '<input type="button" class="a" name="test" value="text" />',
            Html::buttonInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testSubmitInput(): void
    {
        $this->assertEquals('<input type="submit" value="Submit" />', Html::submitInput());
        $this->assertEquals(
            '<input type="submit" class="a" name="test" value="text" />',
            Html::submitInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testResetInput(): void
    {
        $this->assertEquals('<input type="reset" value="Reset" />', Html::resetInput());
        $this->assertEquals(
            '<input type="reset" class="a" name="test" value="text" />',
            Html::resetInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testTextInput(): void
    {
        $this->assertEquals('<input type="text" name="test" />', Html::textInput('test'));
        $this->assertEquals(
            '<input type="text" class="t" name="test" value="value" />',
            Html::textInput('test', 'value', ['class' => 't'])
        );
    }

    public function testHiddenInput(): void
    {
        $this->assertEquals('<input type="hidden" name="test" />', Html::hiddenInput('test'));
        $this->assertEquals(
            '<input type="hidden" class="t" name="test" value="value" />',
            Html::hiddenInput('test', 'value', ['class' => 't'])
        );
    }

    public function testPasswordInput(): void
    {
        $this->assertEquals('<input type="password" name="test" />', Html::passwordInput('test'));
        $this->assertEquals(
            '<input type="password" class="t" name="test" value="value" />',
            Html::passwordInput('test', 'value', ['class' => 't'])
        );
    }

    public function testFileInput(): void
    {
        $this->assertEquals('<input type="file" name="test" />', Html::fileInput('test'));
        $this->assertEquals(
            '<input type="file" class="t" name="test" value="value" />',
            Html::fileInput('test', 'value', ['class' => 't'])
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
        $this->assertEquals($expected, Html::textarea($name, $value, $options));
    }

    public function testRadio(): void
    {
        $this->assertEquals('<input type="radio" name="test" value="1" />', Html::radio('test'));
        $this->assertEquals(
            '<input type="radio" class="a" name="test" checked />',
            Html::radio('test', true, ['class' => 'a', 'value' => null])
        );
        $this->assertEquals(
            '<input type="hidden" name="test" value="0" />'
                . '<input type="radio" class="a" name="test" value="2" checked />',
            Html::radio('test', true, ['class' => 'a', 'uncheck' => '0', 'value' => 2])
        );
        $this->assertEquals(
            '<label class="bbb"><input type="radio" class="a" name="test" checked /> ccc</label>',
            Html::radio('test', true, [
                'class' => 'a',
                'value' => null,
                'label' => 'ccc',
                'labelOptions' => ['class' => 'bbb'],
            ])
        );
        $this->assertEquals(
            '<input type="hidden" name="test" value="0" />'
                . '<label><input type="radio" class="a" name="test" value="2" checked /> ccc</label>',
            Html::radio('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
            ])
        );
    }

    public function testCheckbox(): void
    {
        $this->assertEquals('<input type="checkbox" name="test" value="1" />', Html::checkbox('test'));
        $this->assertEquals(
            '<input type="checkbox" class="a" name="test" checked />',
            Html::checkbox('test', true, ['class' => 'a', 'value' => null])
        );
        $this->assertEquals(
            '<input type="hidden" name="test" value="0" />'
                . '<input type="checkbox" class="a" name="test" value="2" checked />',
            Html::checkbox('test', true, ['class' => 'a', 'uncheck' => '0', 'value' => 2])
        );
        $this->assertEquals(
            '<label class="bbb"><input type="checkbox" class="a" name="test" checked /> ccc</label>',
            Html::checkbox('test', true, [
                'class' => 'a',
                'value' => null,
                'label' => 'ccc',
                'labelOptions' => ['class' => 'bbb'],
            ])
        );
        $this->assertEquals(
            '<input type="hidden" name="test" value="0" />'
                . '<label><input type="checkbox" class="a" name="test" value="2" checked /> ccc</label>',
            Html::checkbox('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
            ])
        );
        $this->assertEquals(
            '<input type="hidden" name="test" value="0" form="test-form" />'
                . '<label><input type="checkbox" class="a" name="test" value="2" form="test-form" checked />'
                . ' ccc</label>',
            Html::checkbox('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
                'form' => 'test-form',
            ])
        );
    }

    public function testDropDownList(): void
    {
        $expected = <<<'EOD'
<select name="test">

</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::dropDownList('test'));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2">text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::dropDownList('test', null, $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::dropDownList('test', 'value2', $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::dropDownList('test', null, $this->getDataItems(), [
            'options' => [
                'value2' => ['selected' => true],
            ],
        ]));
        $expected = <<<'EOD'
<select name="test[]" multiple="true" size="4">

</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::dropDownList('test', null, [], ['multiple' => 'true']));
    }

    public function testListBox(): void
    {
        $expected = <<<'EOD'
<select name="test" size="4">

</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test'));
        $expected = <<<'EOD'
<select name="test" size="5">
<option value="value1">text1</option>
<option value="value2">text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', null, $this->getDataItems(), ['size' => 5]));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1&lt;&gt;</option>
<option value="value  2">text  2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', null, $this->getDataItems2()));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1&lt;&gt;</option>
<option value="value  2">text&nbsp;&nbsp;2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE(
            $expected,
            Html::listBox('test', null, $this->getDataItems2(), ['encodeSpaces' => true])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1<></option>
<option value="value  2">text  2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE(
            $expected,
            Html::listBox('test', null, $this->getDataItems2(), ['encode' => false])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1<></option>
<option value="value  2">text&nbsp;&nbsp;2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE(
            $expected,
            Html::listBox('test', null, $this->getDataItems2(), ['encodeSpaces' => true, 'encode' => false])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', 'value2', $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1" selected>text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', ['value1', 'value2'], $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test[]" multiple size="4">

</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', null, [], ['multiple' => true]));
        $this->assertEqualsWithoutLE($expected, Html::listBox('test[]', null, [], ['multiple' => true]));
        $expected = <<<'EOD'
<input type="hidden" name="test" value="0" /><select name="test" size="4">

</select>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::listBox('test', '', [], ['unselect' => '0']));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1" selected>text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertEqualsWithoutLE(
            $expected,
            Html::listBox('test', new HtmlTraversableSelection(['value1', 'value2']), $this->getDataItems())
        );
    }

    public function testCheckboxList(): void
    {
        $this->assertEquals('<div></div>', Html::checkboxList('test'));
        $expected = <<<'EOD'
<div><label><input type="checkbox" name="test[]" value="value1" /> text1</label>
<label><input type="checkbox" name="test[]" value="value2" checked /> text2</label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', ['value2'], $this->getDataItems()));
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test[]', ['value2'], $this->getDataItems()));
        $expected = <<<'EOD'
<div><label><input type="checkbox" name="test[]" value="value1&lt;&gt;" /> text1&lt;&gt;</label>
<label><input type="checkbox" name="test[]" value="value  2" /> text  2</label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', ['value2'], $this->getDataItems2()));
        $expected = '<input type="hidden" name="test" value="0" />'
            . '<div><label><input type="checkbox" name="test[]" value="value1" /> text1</label><br />' . "\n"
            . '<label><input type="checkbox" name="test[]" value="value2" checked /> text2</label></div>';
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', ['value2'], $this->getDataItems(), [
            'separator' => "<br />\n",
            'unselect' => '0',
        ]));
        $expected = <<<'EOD'
<div>0<label>text1 <input type="checkbox" name="test[]" value="value1" /></label>
1<label>text2 <input type="checkbox" name="test[]" value="value2" checked /></label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
        ]));
        $expected = <<<'EOD'
0<label>text1 <input type="checkbox" name="test[]" value="value1" /></label>
1<label>text2 <input type="checkbox" name="test[]" value="value2" checked /></label>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
            'tag' => false,
        ]));
        $selection = new HtmlTraversableSelection(['value2']);
        $this->assertEqualsWithoutLE($expected, Html::checkboxList('test', $selection, $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
            'tag' => false,
        ]));
    }

    public function testCheckboxListRejectsAnItemOptionThatIsNotCallable(): void
    {
        $this->expectException(InvalidParamException::class);
        Html::checkboxList('test', null, $this->getDataItems(), ['item' => 'not a callable']);
    }

    public function testRadioList(): void
    {
        $this->assertEquals('<div></div>', Html::radioList('test'));
        $expected = <<<'EOD'
<div><label><input type="radio" name="test" value="value1" /> text1</label>
<label><input type="radio" name="test" value="value2" checked /> text2</label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', ['value2'], $this->getDataItems()));
        $expected = <<<'EOD'
<div><label><input type="radio" name="test" value="value1&lt;&gt;" /> text1&lt;&gt;</label>
<label><input type="radio" name="test" value="value  2" /> text  2</label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', ['value2'], $this->getDataItems2()));
        $expected = '<input type="hidden" name="test" value="0" />'
            . '<div><label><input type="radio" name="test" value="value1" /> text1</label><br />' . "\n"
            . '<label><input type="radio" name="test" value="value2" checked /> text2</label></div>';
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', ['value2'], $this->getDataItems(), [
            'separator' => "<br />\n",
            'unselect' => '0',
        ]));
        $expected = <<<'EOD'
<div>0<label>text1 <input type="radio" name="test" value="value1" /></label>
1<label>text2 <input type="radio" name="test" value="value2" checked /></label></div>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
        ]));
        $expected = <<<'EOD'
0<label>text1 <input type="radio" name="test" value="value1" /></label>
1<label>text2 <input type="radio" name="test" value="value2" checked /></label>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
            'tag' => false,
        ]));
        $selection = new HtmlTraversableSelection(['value2']);
        $this->assertEqualsWithoutLE($expected, Html::radioList('test', $selection, $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
            'tag' => false,
        ]));
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
        $this->assertEqualsWithoutLE($expected, Html::ul($data));
        $expected = <<<'EOD'
<ul class="test">
<li class="item-0">1</li>
<li class="item-1">abc</li>
<li class="item-2"><></li>
</ul>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::ul($data, [
            'class' => 'test',
            'item' => $this->listItemFormatter(),
        ]));
        $this->assertEquals('<ul class="test"></ul>', Html::ul([], ['class' => 'test']));
        $this->assertStringMatchesFormat('<foo>%A</foo>', Html::ul([], ['tag' => 'foo']));
    }

    public function testUlRejectsAnItemThatCannotBeRenderedAsText(): void
    {
        $this->expectException(InvalidParamException::class);
        Html::ul([['nested']]);
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
        $this->assertEqualsWithoutLE($expected, Html::ol($data, [
            'itemOptions' => ['class' => 'ti'],
        ]));
        $expected = <<<'EOD'
<ol class="test">
<li class="item-0">1</li>
<li class="item-1">abc</li>
<li class="item-2"><></li>
</ol>
EOD;
        $this->assertEqualsWithoutLE($expected, Html::ol($data, [
            'class' => 'test',
            'item' => $this->listItemFormatter(),
        ]));
        $this->assertEquals('<ol class="test"></ol>', Html::ol([], ['class' => 'test']));
    }

    public function testRenderOptions(): void
    {
        $data = [
            'value1' => 'label1',
            'group1' => [
                'value11' => 'label11',
                'group11' => [
                    'value111' => 'label111',
                ],
                'group12' => [],
            ],
            'value2' => 'label2',
            'group2' => [],
        ];
        $expected = <<<'EOD'
<option value="">please&nbsp;select&lt;&gt;</option>
<option value="value1" selected>label1</option>
<optgroup label="group1">
<option value="value11">label11</option>
<optgroup label="group11">
<option class="option" value="value111" selected>label111</option>
</optgroup>
<optgroup class="group" label="group12">

</optgroup>
</optgroup>
<option value="value2">label2</option>
<optgroup label="group2">

</optgroup>
EOD;
        $attributes = [
            'prompt' => 'please select<>',
            'options' => [
                'value111' => ['class' => 'option'],
            ],
            'groups' => [
                'group12' => ['class' => 'group'],
            ],
            'encodeSpaces' => true,
        ];
        $this->assertEqualsWithoutLE(
            $expected,
            Html::renderSelectOptions(['value111', 'value1'], $data, $attributes)
        );
        $attributes = [
            'prompt' => 'please select<>',
            'options' => [
                'value111' => ['class' => 'option'],
            ],
            'groups' => [
                'group12' => ['class' => 'group'],
            ],
        ];
        $this->assertEqualsWithoutLE(
            str_replace('&nbsp;', ' ', $expected),
            Html::renderSelectOptions(['value111', 'value1'], $data, $attributes)
        );
        // Attributes for prompt (https://github.com/yiisoft/yii2/issues/7420)
        $data = [
            'value1' => 'label1',
            'value2' => 'label2',
        ];
        $expected = <<<'EOD'
<option class="prompt" value="-1" label="None">Please select</option>
<option value="value1" selected>label1</option>
<option value="value2">label2</option>
EOD;
        $attributes = [
            'prompt' => [
                'text' => 'Please select', 'options' => ['class' => 'prompt', 'value' => '-1', 'label' => 'None'],
            ],
        ];
        $this->assertEqualsWithoutLE($expected, Html::renderSelectOptions(['value1'], $data, $attributes));
    }

    public function testRenderAttributes(): void
    {
        $this->assertEquals('', Html::renderTagAttributes([]));
        $this->assertEquals(
            ' name="test" value="1&lt;&gt;"',
            Html::renderTagAttributes(['name' => 'test', 'empty' => null, 'value' => '1<>'])
        );
        $this->assertEquals(
            ' checked disabled',
            Html::renderTagAttributes(['checked' => true, 'disabled' => true, 'hidden' => false])
        );
        $this->assertEquals(' class="first second"', Html::renderTagAttributes(['class' => ['first', 'second']]));
        $this->assertEquals('', Html::renderTagAttributes(['class' => []]));
        $this->assertEquals(
            ' style="width: 100px; height: 200px;"',
            Html::renderTagAttributes(['style' => ['width' => '100px', 'height' => '200px']])
        );
        $this->assertEquals('', Html::renderTagAttributes(['style' => []]));
        $attributes = [
            'data' => [
                'foo' => [],
            ],
        ];
        $this->assertEquals(' data-foo=\'[]\'', Html::renderTagAttributes($attributes));
    }

    public function testAddCssClass(): void
    {
        $options = [];
        Html::addCssClass($options, 'test');
        $this->assertEquals(['class' => 'test'], $options);
        Html::addCssClass($options, 'test');
        $this->assertEquals(['class' => 'test'], $options);
        Html::addCssClass($options, 'test2');
        $this->assertEquals(['class' => 'test test2'], $options);
        Html::addCssClass($options, 'test');
        $this->assertEquals(['class' => 'test test2'], $options);
        Html::addCssClass($options, 'test2');
        $this->assertEquals(['class' => 'test test2'], $options);
        Html::addCssClass($options, 'test3');
        $this->assertEquals(['class' => 'test test2 test3'], $options);
        Html::addCssClass($options, 'test2');
        $this->assertEquals(['class' => 'test test2 test3'], $options);
        $options = [
            'class' => ['test'],
        ];
        Html::addCssClass($options, 'test2');
        $this->assertEquals(['class' => ['test', 'test2']], $options);
        Html::addCssClass($options, 'test2');
        $this->assertEquals(['class' => ['test', 'test2']], $options);
        Html::addCssClass($options, ['test3']);
        $this->assertEquals(['class' => ['test', 'test2', 'test3']], $options);
        $options = [
            'class' => 'test',
        ];
        Html::addCssClass($options, ['test1', 'test2']);
        $this->assertEquals(['class' => 'test test1 test2'], $options);
    }

    #[Depends('testAddCssClass')]
    public function testMergeCssClass(): void
    {
        $options = [
            'class' => [
                'persistent' => 'test1',
            ],
        ];
        Html::addCssClass($options, ['persistent' => 'test2']);
        $this->assertEquals(['persistent' => 'test1'], $options['class']);
        Html::addCssClass($options, ['additional' => 'test2']);
        $this->assertEquals(['persistent' => 'test1', 'additional' => 'test2'], $options['class']);
    }

    public function testRemoveCssClass(): void
    {
        $options = ['class' => 'test test2 test3'];
        Html::removeCssClass($options, 'test2');
        $this->assertEquals(['class' => 'test test3'], $options);
        Html::removeCssClass($options, 'test2');
        $this->assertEquals(['class' => 'test test3'], $options);
        Html::removeCssClass($options, 'test');
        $this->assertEquals(['class' => 'test3'], $options);
        Html::removeCssClass($options, 'test3');
        $this->assertEquals([], $options);
        $options = ['class' => ['test', 'test2', 'test3']];
        Html::removeCssClass($options, 'test2');
        $this->assertEquals(['class' => ['test', 2 => 'test3']], $options);
        Html::removeCssClass($options, 'test');
        Html::removeCssClass($options, 'test3');
        $this->assertEquals([], $options);
        $options = [
            'class' => 'test test1 test2',
        ];
        Html::removeCssClass($options, ['test1', 'test2']);
        $this->assertEquals(['class' => 'test'], $options);
    }

    public function testCssStyleFromArray(): void
    {
        $this->assertEquals('width: 100px; height: 200px;', Html::cssStyleFromArray([
            'width' => '100px',
            'height' => '200px',
        ]));
        $this->assertNull(Html::cssStyleFromArray([]));
    }

    public function testCssStyleToArray(): void
    {
        $this->assertEquals([
            'width' => '100px',
            'height' => '200px',
        ], Html::cssStyleToArray('width: 100px; height: 200px;'));
        $this->assertEquals([], Html::cssStyleToArray('  '));
    }

    public function testAddCssStyle(): void
    {
        $options = ['style' => 'width: 100px; height: 200px;'];
        Html::addCssStyle($options, 'width: 110px; color: red;');
        $this->assertEquals('width: 110px; height: 200px; color: red;', $options['style']);
        $options = ['style' => 'width: 100px; height: 200px;'];
        Html::addCssStyle($options, ['width' => '110px', 'color' => 'red']);
        $this->assertEquals('width: 110px; height: 200px; color: red;', $options['style']);
        $options = ['style' => 'width: 100px; height: 200px;'];
        Html::addCssStyle($options, 'width: 110px; color: red;', false);
        $this->assertEquals('width: 100px; height: 200px; color: red;', $options['style']);
        $options = [];
        Html::addCssStyle($options, 'width: 110px; color: red;');
        $this->assertEquals('width: 110px; color: red;', $options['style']);
        $options = [];
        Html::addCssStyle($options, 'width: 110px; color: red;', false);
        $this->assertEquals('width: 110px; color: red;', $options['style']);
        $options = [
            'style' => [
                'width' => '100px',
            ],
        ];
        Html::addCssStyle($options, ['color' => 'red'], false);
        $this->assertEquals('width: 100px; color: red;', $options['style']);
    }

    public function testRemoveCssStyle(): void
    {
        $options = ['style' => 'width: 110px; height: 200px; color: red;'];
        Html::removeCssStyle($options, 'width');
        $this->assertEquals('height: 200px; color: red;', $options['style']);
        Html::removeCssStyle($options, ['height']);
        $this->assertEquals('color: red;', $options['style']);
        Html::removeCssStyle($options, ['color', 'background']);
        $this->assertNull($options['style']);
        $options = [];
        Html::removeCssStyle($options, ['color', 'background']);
        $this->assertArrayNotHasKey('style', $options);
        $options = [
            'style' => [
                'color' => 'red',
                'width' => '100px',
            ],
        ];
        Html::removeCssStyle($options, ['color']);
        $this->assertEquals('width: 100px;', $options['style']);
    }

    public function testBooleanAttributes(): void
    {
        $this->assertEquals(
            '<input type="email" name="mail" />',
            Html::input('email', 'mail', null, ['required' => false])
        );
        $this->assertEquals(
            '<input type="email" name="mail" required />',
            Html::input('email', 'mail', null, ['required' => true])
        );
        $this->assertEquals(
            '<input type="email" name="mail" required="hi" />',
            Html::input('email', 'mail', null, ['required' => 'hi'])
        );
    }

    public function testDataAttributes(): void
    {
        $this->assertEquals(
            '<link src="xyz" data-a="1" data-b="c" />',
            Html::tag('link', '', ['src' => 'xyz', 'data' => ['a' => 1, 'b' => 'c']])
        );
        $this->assertEquals(
            '<link src="xyz" ng-a="1" ng-b="c" />',
            Html::tag('link', '', ['src' => 'xyz', 'ng' => ['a' => 1, 'b' => 'c']])
        );
        $this->assertEquals(
            '<link src="xyz" data-ng-a="1" data-ng-b="c" />',
            Html::tag('link', '', ['src' => 'xyz', 'data-ng' => ['a' => 1, 'b' => 'c']])
        );
        $this->assertEquals(
            '<link src=\'{"a":1,"b":"It\\u0027s"}\' />',
            Html::tag('link', '', ['src' => ['a' => 1, 'b' => "It's"]])
        );
    }

    /**
     * Data provider for testAttributeNameValidation().
     *
     * @return list<array{string, string}> test data
     */
    public static function validAttributeNamesProvider(): array
    {
        return [
            ['asd]asdf.asdfa[asdfa', 'asdf.asdfa'],
            ['a', 'a'],
            ['[0]a', 'a'],
            ['a[0]', 'a'],
            ['[0]a[0]', 'a'],
            ['[0]a.[0]', 'a.'],
            // Unicode checks.
            ['ä', 'ä'],
            ['ä', 'ä'],
            ['asdf]öáöio..[asdfasdf', 'öáöio..'],
            ['öáöio', 'öáöio'],
            ['[0]test.ööößß.d', 'test.ööößß.d'],
            ['ИІК', 'ИІК'],
            [']ИІК[', 'ИІК'],
            ['[0]ИІК[0]', 'ИІК'],
        ];
    }

    /**
     * Data provider for testAttributeNameException().
     *
     * @return list<array{string}> test data
     */
    public static function invalidAttributeNamesProvider(): array
    {
        return [
            ['. ..'],
            ['a +b'],
            ['a,b'],
        ];
    }

    /**
     * @param string $name
     * @param string $expected
     */
    #[DataProvider('validAttributeNamesProvider')]
    public function testAttributeNameValidation(string $name, string $expected): void
    {
        $this->assertEquals($expected, Html::getAttributeName($name));
    }

    /**
     * @param string $name
     */
    #[DataProvider('invalidAttributeNamesProvider')]
    public function testAttributeNameException(string $name): void
    {
        $this->expectException(InvalidParamException::class);
        Html::getAttributeName($name);
    }

    public function testEscapeJsRegularExpression(): void
    {
        $expected = '/[a-z0-9-]+/';
        $actual = Html::escapeJsRegularExpression('([a-z0-9-]+)');
        $this->assertSame($expected, $actual);
        $expected = '/([a-z0-9-]+)/gim';
        $actual = Html::escapeJsRegularExpression('/([a-z0-9-]+)/Ugimex');
        $this->assertSame($expected, $actual);
    }

    /**
     * Option items whose values and labels need no encoding.
     *
     * @return array<string, string>
     */
    private function getDataItems(): array
    {
        return [
            'value1' => 'text1',
            'value2' => 'text2',
        ];
    }

    /**
     * Option items whose values and labels carry markup and repeated spaces.
     *
     * @return array<string, string>
     */
    private function getDataItems2(): array
    {
        return [
            'value1<>' => 'text1<>',
            'value  2' => 'text  2',
        ];
    }

    /**
     * An `item` callback for checkboxList() that prints the index before a label wrapping the checkbox.
     *
     * @return \Closure(int, string, string, bool, string): string
     */
    private function checkboxItemFormatter(): \Closure
    {
        return static function (int $index, string $label, string $name, bool $checked, string $value): string {
            return $index . Html::label($label . ' ' . Html::checkbox($name, $checked, ['value' => $value]));
        };
    }

    /**
     * An `item` callback for radioList() that prints the index before a label wrapping the radio button.
     *
     * @return \Closure(int, string, string, bool, string): string
     */
    private function radioItemFormatter(): \Closure
    {
        return static function (int $index, string $label, string $name, bool $checked, string $value): string {
            return $index . Html::label($label . ' ' . Html::radio($name, $checked, ['value' => $value]));
        };
    }

    /**
     * An `item` callback for ul() and ol() that prints a list item with an index class.
     *
     * @return \Closure(int|string, int): string
     */
    private function listItemFormatter(): \Closure
    {
        return static function (int|string $item, int $index): string {
            return "<li class=\"item-$index\">$item</li>";
        };
    }

    /**
     * Compares two strings with their line endings normalised to "\n".
     *
     * @param string $expected
     * @param string $actual
     */
    private function assertEqualsWithoutLE(string $expected, string $actual): void
    {
        $this->assertSame(str_replace("\r\n", "\n", $expected), str_replace("\r\n", "\n", $actual));
    }
}
