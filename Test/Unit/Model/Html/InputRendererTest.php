<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\InputRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\InputRenderer
 */
class InputRendererTest extends TestCase
{
    /**
     * @var InputRenderer
     */
    private InputRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlElementServices())->inputRenderer();
    }

    public function testInput(): void
    {
        self::assertSame('<input type="text" />', $this->renderer->input('text'));
        self::assertSame(
            '<input type="text" class="t" name="test" value="value" />',
            $this->renderer->input('text', 'test', 'value', ['class' => 't'])
        );
    }

    public function testButtonInput(): void
    {
        self::assertSame('<input type="button" value="Button" />', $this->renderer->buttonInput());
        self::assertSame(
            '<input type="button" class="a" name="test" value="text" />',
            $this->renderer->buttonInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testSubmitInput(): void
    {
        self::assertSame('<input type="submit" value="Submit" />', $this->renderer->submitInput());
        self::assertSame(
            '<input type="submit" class="a" name="test" value="text" />',
            $this->renderer->submitInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testResetInput(): void
    {
        self::assertSame('<input type="reset" value="Reset" />', $this->renderer->resetInput());
        self::assertSame(
            '<input type="reset" class="a" name="test" value="text" />',
            $this->renderer->resetInput('text', ['name' => 'test', 'class' => 'a'])
        );
    }

    public function testTextInput(): void
    {
        self::assertSame('<input type="text" name="test" />', $this->renderer->textInput('test'));
        self::assertSame(
            '<input type="text" class="t" name="test" value="value" />',
            $this->renderer->textInput('test', 'value', ['class' => 't'])
        );
    }

    public function testHiddenInput(): void
    {
        self::assertSame('<input type="hidden" name="test" />', $this->renderer->hiddenInput('test'));
        self::assertSame(
            '<input type="hidden" class="t" name="test" value="value" />',
            $this->renderer->hiddenInput('test', 'value', ['class' => 't'])
        );
    }

    public function testPasswordInput(): void
    {
        self::assertSame('<input type="password" name="test" />', $this->renderer->passwordInput('test'));
        self::assertSame(
            '<input type="password" class="t" name="test" value="value" />',
            $this->renderer->passwordInput('test', 'value', ['class' => 't'])
        );
    }

    public function testFileInput(): void
    {
        self::assertSame('<input type="file" name="test" />', $this->renderer->fileInput('test'));
        self::assertSame(
            '<input type="file" class="t" name="test" value="value" />',
            $this->renderer->fileInput('test', 'value', ['class' => 't'])
        );
    }

    public function testRadio(): void
    {
        self::assertSame('<input type="radio" name="test" value="1" />', $this->renderer->radio('test'));
        self::assertSame(
            '<input type="radio" class="a" name="test" checked />',
            $this->renderer->radio('test', true, ['class' => 'a', 'value' => null])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" />'
                . '<input type="radio" class="a" name="test" value="2" checked />',
            $this->renderer->radio('test', true, ['class' => 'a', 'uncheck' => '0', 'value' => 2])
        );
        self::assertSame(
            '<label class="bbb"><input type="radio" class="a" name="test" checked /> ccc</label>',
            $this->renderer->radio('test', true, [
                'class' => 'a',
                'value' => null,
                'label' => 'ccc',
                'labelOptions' => ['class' => 'bbb'],
            ])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" />'
                . '<label><input type="radio" class="a" name="test" value="2" checked /> ccc</label>',
            $this->renderer->radio('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
            ])
        );
    }

    public function testCheckbox(): void
    {
        self::assertSame('<input type="checkbox" name="test" value="1" />', $this->renderer->checkbox('test'));
        self::assertSame(
            '<input type="checkbox" class="a" name="test" checked />',
            $this->renderer->checkbox('test', true, ['class' => 'a', 'value' => null])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" />'
                . '<input type="checkbox" class="a" name="test" value="2" checked />',
            $this->renderer->checkbox('test', true, ['class' => 'a', 'uncheck' => '0', 'value' => 2])
        );
        self::assertSame(
            '<label class="bbb"><input type="checkbox" class="a" name="test" checked /> ccc</label>',
            $this->renderer->checkbox('test', true, [
                'class' => 'a',
                'value' => null,
                'label' => 'ccc',
                'labelOptions' => ['class' => 'bbb'],
            ])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" />'
                . '<label><input type="checkbox" class="a" name="test" value="2" checked /> ccc</label>',
            $this->renderer->checkbox('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
            ])
        );
        self::assertSame(
            '<input type="hidden" name="test" value="0" form="test-form" />'
                . '<label><input type="checkbox" class="a" name="test" value="2" form="test-form" checked />'
                . ' ccc</label>',
            $this->renderer->checkbox('test', true, [
                'class' => 'a',
                'uncheck' => '0',
                'label' => 'ccc',
                'value' => 2,
                'form' => 'test-form',
            ])
        );
    }

    public function testBooleanAttributes(): void
    {
        self::assertSame(
            '<input type="email" name="mail" />',
            $this->renderer->input('email', 'mail', null, ['required' => false])
        );
        self::assertSame(
            '<input type="email" name="mail" required />',
            $this->renderer->input('email', 'mail', null, ['required' => true])
        );
        self::assertSame(
            '<input type="email" name="mail" required="hi" />',
            $this->renderer->input('email', 'mail', null, ['required' => 'hi'])
        );
    }

    public function testATypeOptionTakesPriorityOverTheTypeArgument(): void
    {
        self::assertSame('<input type="email" />', $this->renderer->input('text', null, null, ['type' => 'email']));
    }

    public function testValueIsCastToAString(): void
    {
        self::assertSame('<input type="text" value="5" />', $this->renderer->input('text', null, 5));
        self::assertSame('<input type="text" value="1" />', $this->renderer->input('text', null, true));
        self::assertSame('<input type="text" value="" />', $this->renderer->input('text', null, false));
    }

    public function testButtonInputsWithoutALabelHaveNoValue(): void
    {
        self::assertSame('<input type="button" />', $this->renderer->buttonInput(null));
        self::assertSame('<input type="submit" />', $this->renderer->submitInput(null));
        self::assertSame('<input type="reset" />', $this->renderer->resetInput(null));
    }

    public function testButtonInputsForceTheirType(): void
    {
        self::assertSame(
            '<input type="submit" value="Submit" />',
            $this->renderer->submitInput('Submit', ['type' => 'button'])
        );
    }

    public function testACheckedOptionTakesPriorityOverTheArgument(): void
    {
        self::assertSame(
            '<input type="checkbox" name="test" value="1" />',
            $this->renderer->checkbox('test', true, ['checked' => false])
        );
        self::assertSame(
            '<input type="radio" name="test" value="1" checked />',
            $this->renderer->radio('test', false, ['checked' => true])
        );
    }

    public function testTheUncheckedHiddenInputOfADisabledInputSendsNoValue(): void
    {
        self::assertSame(
            '<input type="hidden" name="test" value="0" disabled />'
                . '<input type="checkbox" name="test" value="1" disabled />',
            $this->renderer->checkbox('test', false, ['uncheck' => '0', 'disabled' => true])
        );
    }

    public function testAnUncheckSetToNullRendersNoHiddenInput(): void
    {
        self::assertSame(
            '<input type="checkbox" name="test" value="1" />',
            $this->renderer->checkbox('test', false, ['uncheck' => null])
        );
    }

    public function testTheForAttributeOfLabelOptionsIsNotRendered(): void
    {
        self::assertSame(
            '<label class="l"><input type="radio" name="test" value="1" /> text</label>',
            $this->renderer->radio('test', false, [
                'label' => 'text',
                'labelOptions' => ['class' => 'l', 'for' => 'other'],
            ])
        );
    }

    public function testTheLabelIsPrintedAsGiven(): void
    {
        self::assertSame(
            '<label><input type="checkbox" name="test" value="1" /> <b>text</b></label>',
            $this->renderer->checkbox('test', false, ['label' => '<b>text</b>'])
        );
    }

    public function testACheckboxValueWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->checkbox('test', false, ['value' => new \stdClass()]);
    }

    public function testAnUncheckValueWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->radio('test', false, ['uncheck' => ['0']]);
    }

    public function testALabelWithoutTextFormIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->checkbox('test', false, ['label' => new \stdClass()]);
    }
}
