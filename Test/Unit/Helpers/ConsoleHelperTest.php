<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers;

use Hryvinskyi\Base\Helper\ConsoleHelper;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\ConsoleHelperScripted;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConsoleHelperTest extends TestCase
{
    /**
     * Ends any progress bar a test started, so that its state does not leak into the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ConsoleHelperScripted::script([]);
        ConsoleHelperScripted::endProgress();
    }

    /**
     * An empty answer returns the default, which the prompt shows in brackets.
     *
     * @return void
     */
    public function testPromptReturnsDefaultOnEmptyInput(): void
    {
        ConsoleHelperScripted::script(['']);

        $this->assertSame('blue', ConsoleHelperScripted::prompt('Color?', ['default' => 'blue']));
        $this->assertSame('Color? [blue] ', ConsoleHelperScripted::written());
    }

    /**
     * An empty answer to an optional question is returned as it is.
     *
     * @return void
     */
    public function testPromptReturnsEmptyInputWhenNotRequired(): void
    {
        ConsoleHelperScripted::script(['']);

        $this->assertSame('', ConsoleHelperScripted::prompt('Name?'));
        $this->assertSame('Name? ', ConsoleHelperScripted::written());
    }

    /**
     * An empty answer to a required question prints the error and asks again.
     *
     * @return void
     */
    public function testPromptAsksAgainWhenRequiredInputIsEmpty(): void
    {
        ConsoleHelperScripted::script(['', 'Ann']);

        $this->assertSame('Ann', ConsoleHelperScripted::prompt('Name?', ['required' => true, 'error' => 'Needed.']));
        $this->assertSame('Name? Needed.' . PHP_EOL . 'Name? ', ConsoleHelperScripted::written());
    }

    /**
     * An answer that does not match the pattern prints the error and asks again.
     *
     * @return void
     */
    public function testPromptAsksAgainWhenPatternDoesNotMatch(): void
    {
        ConsoleHelperScripted::script(['abc', '42']);

        $this->assertSame('42', ConsoleHelperScripted::prompt('Age?', ['pattern' => '/^\d+$/']));
        $this->assertSame('Age? Invalid input.' . PHP_EOL . 'Age? ', ConsoleHelperScripted::written());
    }

    /**
     * A refused answer prints the error the validator set by reference, and asks again.
     *
     * @return void
     */
    public function testPromptPrintsTheValidatorError(): void
    {
        ConsoleHelperScripted::script(['ab', 'abcd']);
        $validator = static function (string $input, mixed &$error): bool {
            if (strlen($input) < 3) {
                $error = 'Too short.';

                return false;
            }

            return true;
        };

        $this->assertSame('abcd', ConsoleHelperScripted::prompt('Code?', ['validator' => $validator]));
        $this->assertSame('Code? Too short.' . PHP_EOL . 'Code? ', ConsoleHelperScripted::written());
    }

    /**
     * A validator that refuses without setting an error makes the prompt print the default error.
     *
     * @return void
     */
    public function testPromptPrintsTheDefaultErrorWhenValidatorSetsNone(): void
    {
        ConsoleHelperScripted::script(['no', 'yes']);
        $validator = static fn (string $input): bool => $input === 'yes';

        $this->assertSame('yes', ConsoleHelperScripted::prompt('Sure?', ['validator' => $validator]));
        $this->assertSame('Sure? Invalid input.' . PHP_EOL . 'Sure? ', ConsoleHelperScripted::written());
    }

    /**
     * '?' lists the options, an unknown key asks again, a known key is returned.
     *
     * @return void
     */
    public function testSelectListsOptionsAndAsksUntilAKnownKey(): void
    {
        ConsoleHelperScripted::script(['?', 'z', 'b']);

        $this->assertSame('b', ConsoleHelperScripted::select('Pick', ['a' => 'Alpha', 'b' => 'Beta']));
        $prompt = 'Pick [a,b,?]: ';
        $this->assertSame(
            $prompt . ' a - Alpha' . PHP_EOL . ' b - Beta' . PHP_EOL . ' ? - Show help' . PHP_EOL
            . $prompt . $prompt,
            ConsoleHelperScripted::written()
        );
    }

    /**
     * Data provider for testUpdateProgressDrawsTheBar().
     *
     * @return array<string, array{array{int, int}|false, int|float|false|null, string}>
     */
    public static function dataProviderProgress(): array
    {
        return [
            'full screen width' => [[40, 20], null, "\rP [========>       ] 50% (5/10) ETA: n/a"],
            'share of the screen' => [[40, 20], 0.75, "\rP [===>  ] 50% (5/10) ETA: n/a"],
            'fixed width without a known screen' => [false, 30, "\rP [===>  ] 50% (5/10) ETA: n/a"],
            'no bar' => [[40, 20], false, "\rP 50% (5/10) ETA: n/a   "],
            'unknown screen and no width' => [false, null, "\rP 50% (5/10) ETA: n/a   "],
        ];
    }

    /**
     * The progress bar fills the width it is given, after the prefix and the status information.
     *
     * @param array{int,int}|false $screen the screen size
     * @param int|float|false|null $width the requested width of the bar
     * @param string $expected the line written
     * @return void
     */
    #[DataProvider('dataProviderProgress')]
    public function testUpdateProgressDrawsTheBar(
        array|false $screen,
        int|float|false|null $width,
        string $expected
    ): void {
        ConsoleHelperScripted::script([], $screen);

        ConsoleHelperScripted::startProgress(5, 10, 'P ', $width);

        $this->assertSame($expected, ConsoleHelperScripted::written());
    }

    /**
     * A stream or value that is not a terminal does not support colors.
     *
     * @return void
     */
    public function testStreamThatIsNotATerminalDoesNotSupportColors(): void
    {
        $stream = fopen('php://memory', 'r');
        $this->assertIsResource($stream);

        $this->assertFalse(ConsoleHelper::streamSupportsAnsiColors($stream));
        $this->assertFalse(ConsoleHelper::streamSupportsAnsiColors(999));
        fclose($stream);
    }

    /**
     * Color markers become ANSI codes, or are removed when colors are off; %% stands for a percent sign.
     *
     * @return void
     */
    public function testRenderColoredString(): void
    {
        $this->assertSame("\033[33mtext 100%", ConsoleHelper::renderColoredString('%ytext 100%%'));
        $this->assertSame('text 100%', ConsoleHelper::renderColoredString('%ytext 100%%', false));
    }

    /**
     * ANSI codes become styled spans; list values are joined, and negative swaps the colors.
     *
     * @return void
     */
    public function testAnsiToHtml(): void
    {
        $this->assertSame(
            '<span style="color: red;">red</span>',
            ConsoleHelper::ansiToHtml("\033[31mred\033[0m")
        );
        $this->assertSame(
            '<span style="text-decoration: underline overline;">x</span>',
            ConsoleHelper::ansiToHtml("\033[4;53mx")
        );
        $this->assertSame(
            '<span style="background-color: red;color: lime;">x</span>',
            ConsoleHelper::ansiToHtml("\033[7;31;42mx")
        );
    }

    /**
     * ANSI codes are not counted in the length and are removed by stripping.
     *
     * @return void
     */
    public function testStripAnsiFormat(): void
    {
        $formatted = ConsoleHelper::ansiFormat('abc', [ConsoleHelper::FG_RED, ConsoleHelper::xtermBgColor(5)]);

        $this->assertSame("\033[0m\033[31;48;5;5mabc\033[0m", $formatted);
        $this->assertSame('abc', ConsoleHelper::stripAnsiFormat($formatted));
        $this->assertSame(3, ConsoleHelper::ansiStrlen($formatted));
    }

    /**
     * Text wraps to the screen width, with every line but the first indented.
     *
     * @return void
     */
    public function testWrapText(): void
    {
        ConsoleHelperScripted::script([], [16, 20]);

        $this->assertSame(
            "Lorem ipsum\n    dolor sit\n    amet.",
            ConsoleHelperScripted::wrapText('Lorem ipsum dolor sit amet.', 4)
        );
    }

    /**
     * Cursor movement is sent to the output as an ANSI control sequence.
     *
     * @return void
     */
    public function testMoveCursorToWritesTheControlSequence(): void
    {
        $this->expectOutputString("\033[5G\033[3;5H");

        ConsoleHelper::moveCursorTo(5);
        ConsoleHelper::moveCursorTo(5, 3);
    }
}
