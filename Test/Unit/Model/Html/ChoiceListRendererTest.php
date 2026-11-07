<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Api\Html\ChoiceListRendererInterface;
use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\ChoiceListRenderer;
use Hryvinskyi\Base\Model\Html\FormRenderer;
use Hryvinskyi\Base\Model\Html\InputRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlTraversableSelection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\ChoiceListRenderer
 */
class ChoiceListRendererTest extends TestCase
{
    /**
     * @var ChoiceListRenderer
     */
    private ChoiceListRenderer $renderer;

    /**
     * @var FormRenderer
     */
    private FormRenderer $forms;

    /**
     * @var InputRenderer
     */
    private InputRenderer $inputs;

    protected function setUp(): void
    {
        $services = new HtmlElementServices();
        $this->renderer = $services->choiceListRenderer();
        $this->forms = $services->formRenderer();
        $this->inputs = $services->inputRenderer();
    }

    public function testDropDownList(): void
    {
        $expected = <<<'EOD'
<select name="test">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test'));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2">text2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test', null, $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test', 'value2', $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test', null, $this->getDataItems(), [
            'options' => [
                'value2' => ['selected' => true],
            ],
        ]));
        $expected = <<<'EOD'
<select name="test[]" multiple="true" size="4">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test', null, [], ['multiple' => 'true']));
    }

    public function testListBox(): void
    {
        $expected = <<<'EOD'
<select name="test" size="4">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test'));
        $expected = <<<'EOD'
<select name="test" size="5">
<option value="value1">text1</option>
<option value="value2">text2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, $this->getDataItems(), ['size' => 5]));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1&lt;&gt;</option>
<option value="value  2">text  2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, $this->getDataItems2()));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1&lt;&gt;</option>
<option value="value  2">text&nbsp;&nbsp;2</option>
</select>
EOD;
        $this->assertSameLines(
            $expected,
            $this->renderer->listBox('test', null, $this->getDataItems2(), ['encodeSpaces' => true])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1<></option>
<option value="value  2">text  2</option>
</select>
EOD;
        $this->assertSameLines(
            $expected,
            $this->renderer->listBox('test', null, $this->getDataItems2(), ['encode' => false])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1&lt;&gt;">text1<></option>
<option value="value  2">text&nbsp;&nbsp;2</option>
</select>
EOD;
        $this->assertSameLines(
            $expected,
            $this->renderer->listBox('test', null, $this->getDataItems2(), ['encodeSpaces' => true, 'encode' => false])
        );
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1">text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', 'value2', $this->getDataItems()));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1" selected>text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertSameLines(
            $expected,
            $this->renderer->listBox('test', ['value1', 'value2'], $this->getDataItems())
        );
        $expected = <<<'EOD'
<select name="test[]" multiple size="4">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, [], ['multiple' => true]));
        $this->assertSameLines($expected, $this->renderer->listBox('test[]', null, [], ['multiple' => true]));
        $expected = <<<'EOD'
<input type="hidden" name="test" value="0" /><select name="test" size="4">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', '', [], ['unselect' => '0']));
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="value1" selected>text1</option>
<option value="value2" selected>text2</option>
</select>
EOD;
        $this->assertSameLines(
            $expected,
            $this->renderer->listBox('test', new HtmlTraversableSelection(['value1', 'value2']), $this->getDataItems())
        );
    }

    public function testCheckboxList(): void
    {
        self::assertSame('<div></div>', $this->renderer->checkboxList('test'));
        $expected = <<<'EOD'
<div><label><input type="checkbox" name="test[]" value="value1" /> text1</label>
<label><input type="checkbox" name="test[]" value="value2" checked /> text2</label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', ['value2'], $this->getDataItems()));
        $this->assertSameLines($expected, $this->renderer->checkboxList('test[]', ['value2'], $this->getDataItems()));
        $expected = <<<'EOD'
<div><label><input type="checkbox" name="test[]" value="value1&lt;&gt;" /> text1&lt;&gt;</label>
<label><input type="checkbox" name="test[]" value="value  2" /> text  2</label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', ['value2'], $this->getDataItems2()));
        $expected = '<input type="hidden" name="test" value="0" />'
            . '<div><label><input type="checkbox" name="test[]" value="value1" /> text1</label><br />' . "\n"
            . '<label><input type="checkbox" name="test[]" value="value2" checked /> text2</label></div>';
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', ['value2'], $this->getDataItems(), [
            'separator' => "<br />\n",
            'unselect' => '0',
        ]));
        $expected = <<<'EOD'
<div>0<label>text1 <input type="checkbox" name="test[]" value="value1" /></label>
1<label>text2 <input type="checkbox" name="test[]" value="value2" checked /></label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
        ]));
        $expected = <<<'EOD'
0<label>text1 <input type="checkbox" name="test[]" value="value1" /></label>
1<label>text2 <input type="checkbox" name="test[]" value="value2" checked /></label>
EOD;
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
            'tag' => false,
        ]));
        $selection = new HtmlTraversableSelection(['value2']);
        $this->assertSameLines($expected, $this->renderer->checkboxList('test', $selection, $this->getDataItems(), [
            'item' => $this->checkboxItemFormatter(),
            'tag' => false,
        ]));
    }

    public function testCheckboxListRejectsAnItemOptionThatIsNotCallable(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->checkboxList('test', null, $this->getDataItems(), ['item' => 'not a callable']);
    }

    public function testRadioList(): void
    {
        self::assertSame('<div></div>', $this->renderer->radioList('test'));
        $expected = <<<'EOD'
<div><label><input type="radio" name="test" value="value1" /> text1</label>
<label><input type="radio" name="test" value="value2" checked /> text2</label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->radioList('test', ['value2'], $this->getDataItems()));
        $expected = <<<'EOD'
<div><label><input type="radio" name="test" value="value1&lt;&gt;" /> text1&lt;&gt;</label>
<label><input type="radio" name="test" value="value  2" /> text  2</label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->radioList('test', ['value2'], $this->getDataItems2()));
        $expected = '<input type="hidden" name="test" value="0" />'
            . '<div><label><input type="radio" name="test" value="value1" /> text1</label><br />' . "\n"
            . '<label><input type="radio" name="test" value="value2" checked /> text2</label></div>';
        $this->assertSameLines($expected, $this->renderer->radioList('test', ['value2'], $this->getDataItems(), [
            'separator' => "<br />\n",
            'unselect' => '0',
        ]));
        $expected = <<<'EOD'
<div>0<label>text1 <input type="radio" name="test" value="value1" /></label>
1<label>text2 <input type="radio" name="test" value="value2" checked /></label></div>
EOD;
        $this->assertSameLines($expected, $this->renderer->radioList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
        ]));
        $expected = <<<'EOD'
0<label>text1 <input type="radio" name="test" value="value1" /></label>
1<label>text2 <input type="radio" name="test" value="value2" checked /></label>
EOD;
        $this->assertSameLines($expected, $this->renderer->radioList('test', ['value2'], $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
            'tag' => false,
        ]));
        $selection = new HtmlTraversableSelection(['value2']);
        $this->assertSameLines($expected, $this->renderer->radioList('test', $selection, $this->getDataItems(), [
            'item' => $this->radioItemFormatter(),
            'tag' => false,
        ]));
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
        $this->assertSameLines(
            $expected,
            $this->renderer->renderSelectOptions(['value111', 'value1'], $data, $attributes)
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
        $this->assertSameLines(
            str_replace('&nbsp;', ' ', $expected),
            $this->renderer->renderSelectOptions(['value111', 'value1'], $data, $attributes)
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
        $this->assertSameLines($expected, $this->renderer->renderSelectOptions(['value1'], $data, $attributes));
    }

    public function testDropDownListKeepsTheSelectOptionKeysOffTheTag(): void
    {
        $expected = <<<'EOD'
<select class="s" name="test">
<option class="p" value="">please&nbsp;select&lt;&gt;</option>
<option class="o" value="value1">text1</option>
<optgroup class="g" label="group">
<option value="value2" selected>text2</option>
</optgroup>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList(
            'test',
            'value2',
            ['value1' => 'text1', 'group' => ['value2' => 'text2']],
            [
                'class' => 's',
                'prompt' => ['text' => 'please select<>', 'options' => ['class' => 'p']],
                'options' => ['value1' => ['class' => 'o']],
                'groups' => ['group' => ['class' => 'g']],
                'encode' => true,
                'encodeSpaces' => true,
                'strict' => true,
            ]
        ));
    }

    public function testDropDownListIgnoresUnselect(): void
    {
        $expected = <<<'EOD'
<select name="test">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->dropDownList('test', null, [], ['unselect' => '0']));
    }

    public function testListBoxKeepsTheSelectOptionKeysOffTheTag(): void
    {
        $expected = <<<'EOD'
<select name="test" size="4">
<option value="">please&nbsp;select</option>
<option value="value1">text1<></option>
</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, ['value1' => 'text1<>'], [
            'prompt' => 'please select',
            'options' => [],
            'groups' => [],
            'encode' => false,
            'encodeSpaces' => true,
            'strict' => false,
        ]));
    }

    public function testListBoxHiddenInputDropsTheBracketsAndTakesDisabled(): void
    {
        $expected = <<<'EOD'
<input type="hidden" name="test" value="0" disabled /><select name="test[]" disabled multiple size="4">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, [], [
            'multiple' => true,
            'unselect' => '0',
            'disabled' => true,
        ]));
    }

    public function testListBoxKeepsASizeSetToNull(): void
    {
        $expected = <<<'EOD'
<select name="test">

</select>
EOD;
        $this->assertSameLines($expected, $this->renderer->listBox('test', null, [], ['size' => null]));
    }

    public function testSelectOptionKeysListWhatRenderSelectOptionsReads(): void
    {
        self::assertSame(
            ['prompt', 'options', 'groups', 'encode', 'encodeSpaces', 'strict'],
            ChoiceListRendererInterface::SELECT_OPTION_KEYS
        );
    }

    public function testRenderSelectOptionsIgnoresOtherKeys(): void
    {
        self::assertSame(
            '<option value="a">b</option>',
            $this->renderer->renderSelectOptions(null, ['a' => 'b'], ['name' => 'test', 'class' => 'c'])
        );
    }

    public function testAnEncodeOptionSetToNullIsOffRatherThanTheDefault(): void
    {
        self::assertSame(
            '<option value="a">&lt;b</option>' . "\n" . '<option value="c"><b</option>',
            $this->renderer->renderSelectOptions(null, ['a' => '<b'])
                . "\n"
                . $this->renderer->renderSelectOptions(null, ['c' => '<b'], ['encode' => null])
        );
    }

    public function testASelectedAttributeReplacesTheComparison(): void
    {
        self::assertSame(
            '<option value="a">b</option>',
            $this->renderer->renderSelectOptions('a', ['a' => 'b'], ['options' => ['a' => ['selected' => false]]])
        );
    }

    public function testALooseSelectionMatchesNumericTextAndAStrictOneComparesText(): void
    {
        $items = ['1' => 'one', '01' => 'padded'];

        self::assertSame(
            '<option value="1" selected>one</option>' . "\n" . '<option value="01" selected>padded</option>',
            $this->renderer->renderSelectOptions(1, $items)
        );
        self::assertSame(
            '<option value="1" selected>one</option>' . "\n" . '<option value="01">padded</option>',
            $this->renderer->renderSelectOptions(1, $items, ['strict' => true])
        );
    }

    public function testAStrictSelectionListComparesAsText(): void
    {
        self::assertSame(
            '<option value="1" selected>one</option>' . "\n" . '<option value="01">padded</option>',
            $this->renderer->renderSelectOptions(['1'], ['1' => 'one', '01' => 'padded'], ['strict' => true])
        );
    }

    public function testAnEmptyOptionValueMatchesOnlyAnEmptySelection(): void
    {
        self::assertSame(
            '<option value="" selected>none</option>' . "\n" . '<option value="0">zero</option>',
            $this->renderer->renderSelectOptions('', ['' => 'none', 0 => 'zero'])
        );
        self::assertSame(
            '<option value="">none</option>' . "\n" . '<option value="0" selected>zero</option>',
            $this->renderer->renderSelectOptions(0, ['' => 'none', 0 => 'zero'])
        );
    }

    public function testABooleanSelectionMatchesOneAndZero(): void
    {
        $items = [1 => 'yes', 0 => 'no'];

        self::assertSame(
            '<option value="1" selected>yes</option>' . "\n" . '<option value="0">no</option>',
            $this->renderer->renderSelectOptions(true, $items)
        );
        self::assertSame(
            '<option value="1">yes</option>' . "\n" . '<option value="0" selected>no</option>',
            $this->renderer->renderSelectOptions(false, $items)
        );
        self::assertSame(
            '<option value="1" selected>yes</option>' . "\n" . '<option value="0">no</option>',
            $this->renderer->renderSelectOptions([true], $items)
        );
    }

    public function testAStringableSelectionIsComparedAsItsText(): void
    {
        $selection = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'a';
            }
        };

        self::assertSame(
            '<option value="a" selected>b</option>',
            $this->renderer->renderSelectOptions($selection, ['a' => 'b'])
        );
    }

    public function testASelectionWithoutTextFormSelectsNothing(): void
    {
        self::assertSame(
            '<option value="a">b</option>',
            $this->renderer->renderSelectOptions(new \stdClass(), ['a' => 'b'])
        );
    }

    public function testRenderSelectOptionsRejectsALabelWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->renderSelectOptions(null, ['a' => new \stdClass()]);
    }

    public function testRenderSelectOptionsRejectsASelectionValueWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->renderSelectOptions([['nested']], ['a' => 'b']);
    }

    public function testCheckboxListWithANullOrFalseTagRendersNoContainer(): void
    {
        $expected = '<input type="hidden" name="test" value="0" />'
            . '<label><input type="checkbox" name="test[]" value="value1" /> text1</label>' . "\n"
            . '<label><input type="checkbox" name="test[]" value="value2" /> text2</label>';

        foreach ([null, false] as $tag) {
            self::assertSame($expected, $this->renderer->checkboxList('test', null, $this->getDataItems(), [
                'tag' => $tag,
                'unselect' => '0',
            ]));
        }
    }

    public function testRadioListWithANullOrFalseTagRendersNoContainer(): void
    {
        $expected = '<label><input type="radio" name="test" value="value1" /> text1</label>' . "\n"
            . '<label><input type="radio" name="test" value="value2" /> text2</label>';

        foreach ([null, false] as $tag) {
            self::assertSame(
                $expected,
                $this->renderer->radioList('test', null, $this->getDataItems(), ['tag' => $tag])
            );
        }
    }

    public function testListsRenderTheConfiguredContainerWithItsAttributes(): void
    {
        self::assertSame('<ul class="c"></ul>', $this->renderer->checkboxList('test', null, [], [
            'tag' => 'ul',
            'class' => 'c',
        ]));
        self::assertSame('<ul class="c"></ul>', $this->renderer->radioList('test', null, [], [
            'tag' => 'ul',
            'class' => 'c',
        ]));
    }

    public function testUnselectMovesDisabledFromTheContainerToTheHiddenInput(): void
    {
        self::assertSame(
            '<input type="hidden" name="test" value="0" disabled /><div></div>',
            $this->renderer->checkboxList('test', null, [], ['unselect' => '0', 'disabled' => true])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" disabled /><div></div>',
            $this->renderer->radioList('test', null, [], ['unselect' => '0', 'disabled' => true])
        );
    }

    public function testDisabledStaysOnTheContainerWithoutUnselect(): void
    {
        self::assertSame('<div disabled></div>', $this->renderer->checkboxList('test', null, [], ['disabled' => true]));
    }

    public function testItemOptionsOverrideValueAndLabel(): void
    {
        self::assertSame(
            '<div><label><input type="checkbox" class="i" name="test[]" value="v" /> l</label></div>',
            $this->renderer->checkboxList('test', null, ['value1' => 'text1'], [
                'itemOptions' => ['class' => 'i', 'value' => 'v', 'label' => 'l'],
            ])
        );
    }

    public function testEncodeOffPrintsTheLabelAsGiven(): void
    {
        self::assertSame(
            '<div><label><input type="radio" name="test" value="a" /> <b></label></div>',
            $this->renderer->radioList('test', null, ['a' => '<b>'], ['encode' => false])
        );
    }

    public function testASingleSelectionChecksTheItemWithTheSameText(): void
    {
        self::assertSame(
            '<div><label><input type="radio" name="test" value="1" checked /> one</label></div>',
            $this->renderer->radioList('test', 1, [1 => 'one'])
        );
    }

    public function testAStrictListSelectionComparesAsText(): void
    {
        self::assertSame(
            '<div><label><input type="checkbox" name="test[]" value="1" checked /> one</label>' . "\n"
                . '<label><input type="checkbox" name="test[]" value="01" /> padded</label></div>',
            $this->renderer->checkboxList('test', ['1'], ['1' => 'one', '01' => 'padded'], ['strict' => true])
        );
    }

    public function testTheItemCallbackGetsTheKeyOfTheItemAsItIs(): void
    {
        self::assertSame(
            "<div>integer\nstring</div>",
            $this->renderer->radioList('test', null, [5 => 'five', 'a' => 'letter'], [
                'item' => static function (int $index, string $label, ?string $name, bool $checked, int|string $value) {
                    return get_debug_type($value) === 'int' ? 'integer' : 'string';
                },
            ])
        );
    }

    public function testRadioListRejectsAnItemOptionThatIsNotCallable(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->radioList('test', null, $this->getDataItems(), ['item' => 'not a callable']);
    }

    public function testCheckboxListRejectsAContainerTagWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->checkboxList('test', null, [], ['tag' => ['div']]);
    }

    public function testCheckboxListRejectsASelectionWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->checkboxList('test', new \stdClass());
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
        return function (int $index, string $label, string $name, bool $checked, string $value): string {
            $input = $this->inputs->checkbox($name, $checked, ['value' => $value]);

            return $index . $this->forms->label($label . ' ' . $input);
        };
    }

    /**
     * An `item` callback for radioList() that prints the index before a label wrapping the radio button.
     *
     * @return \Closure(int, string, string, bool, string): string
     */
    private function radioItemFormatter(): \Closure
    {
        return function (int $index, string $label, string $name, bool $checked, string $value): string {
            $input = $this->inputs->radio($name, $checked, ['value' => $value]);

            return $index . $this->forms->label($label . ' ' . $input);
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
