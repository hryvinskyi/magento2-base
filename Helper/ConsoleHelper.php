<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

/**
 * Console output helpers: ANSI cursor and screen control, ANSI text formatting, prompts and a progress bar.
 *
 * @phpstan-type PromptOptions array{
 *     required?: bool,
 *     default?: string|null,
 *     pattern?: string|null,
 *     validator?: (callable(string, mixed &): bool)|null,
 *     error?: string
 * }
 */
class ConsoleHelper
{
    // foreground color control codes
    public const FG_BLACK  = 30;
    public const FG_RED    = 31;
    public const FG_GREEN  = 32;
    public const FG_YELLOW = 33;
    public const FG_BLUE   = 34;
    public const FG_PURPLE = 35;
    public const FG_CYAN   = 36;
    public const FG_GREY   = 37;

    // background color control codes
    public const BG_BLACK  = 40;
    public const BG_RED    = 41;
    public const BG_GREEN  = 42;
    public const BG_YELLOW = 43;
    public const BG_BLUE   = 44;
    public const BG_PURPLE = 45;
    public const BG_CYAN   = 46;
    public const BG_GREY   = 47;

    // fonts style control codes
    public const RESET       = 0;
    public const NORMAL      = 0;
    public const BOLD        = 1;
    public const ITALIC      = 3;
    public const UNDERLINE   = 4;
    public const BLINK       = 5;
    public const NEGATIVE    = 7;
    public const CONCEALED   = 8;
    public const CROSSED_OUT = 9;
    public const FRAMED      = 51;
    public const ENCIRCLED   = 52;
    public const OVERLINED   = 53;

    /**
     * Color markers understood by renderColoredString(), mapped to the ANSI codes they stand for.
     */
    private const COLOR_CONVERSIONS = [
        '%y' => [self::FG_YELLOW],
        '%g' => [self::FG_GREEN],
        '%b' => [self::FG_BLUE],
        '%r' => [self::FG_RED],
        '%p' => [self::FG_PURPLE],
        '%m' => [self::FG_PURPLE],
        '%c' => [self::FG_CYAN],
        '%w' => [self::FG_GREY],
        '%k' => [self::FG_BLACK],
        '%n' => [0], // reset
        '%Y' => [self::FG_YELLOW, self::BOLD],
        '%G' => [self::FG_GREEN, self::BOLD],
        '%B' => [self::FG_BLUE, self::BOLD],
        '%R' => [self::FG_RED, self::BOLD],
        '%P' => [self::FG_PURPLE, self::BOLD],
        '%M' => [self::FG_PURPLE, self::BOLD],
        '%C' => [self::FG_CYAN, self::BOLD],
        '%W' => [self::FG_GREY, self::BOLD],
        '%K' => [self::FG_BLACK, self::BOLD],
        '%N' => [0, self::BOLD],
        '%3' => [self::BG_YELLOW],
        '%2' => [self::BG_GREEN],
        '%4' => [self::BG_BLUE],
        '%1' => [self::BG_RED],
        '%5' => [self::BG_PURPLE],
        '%6' => [self::BG_CYAN],
        '%7' => [self::BG_GREY],
        '%0' => [self::BG_BLACK],
        '%F' => [self::BLINK],
        '%U' => [self::UNDERLINE],
        '%8' => [self::NEGATIVE],
        '%9' => [self::BOLD],
        '%_' => [self::BOLD],
    ];

    /**
     * Unix timestamp at which the current progress bar was started.
     *
     * @var int|null
     */
    private static $_progressStart;

    /**
     * Width of the current progress bar: characters, a share of the screen, false for none, null for full width.
     *
     * @var int|float|false|null
     */
    private static $_progressWidth;

    /**
     * Text displayed before the current progress bar.
     *
     * @var string
     */
    private static $_progressPrefix = '';

    /**
     * Estimated seconds left for the current progress bar, null while unknown.
     *
     * @var int|float|null
     */
    private static $_progressEta;

    /**
     * Number of items done at the last ETA calculation.
     *
     * @var int
     */
    private static $_progressEtaLastDone = 0;

    /**
     * Unix timestamp of the last ETA calculation.
     *
     * @var int|null
     */
    private static $_progressEtaLastUpdate;

    /**
     * Terminal size detected by getScreenSize(), false when it could not be detected, null before detection.
     *
     * @var array{int,int}|false|null
     */
    private static $screenSize;

    /**
     * Moves the terminal cursor up by sending ANSI control code CUU to the terminal.
     *
     * If the cursor is already at the edge of the screen, this has no effect.
     *
     * @param int $rows number of rows the cursor should be moved up
     * @return void
     */
    public static function moveCursorUp($rows = 1)
    {
        self::writeControlSequence("\033[" . (int) $rows . 'A');
    }

    /**
     * Moves the terminal cursor down by sending ANSI control code CUD to the terminal.
     *
     * If the cursor is already at the edge of the screen, this has no effect.
     *
     * @param int $rows number of rows the cursor should be moved down
     * @return void
     */
    public static function moveCursorDown($rows = 1)
    {
        self::writeControlSequence("\033[" . (int) $rows . 'B');
    }

    /**
     * Moves the terminal cursor forward by sending ANSI control code CUF to the terminal.
     *
     * If the cursor is already at the edge of the screen, this has no effect.
     *
     * @param int $steps number of steps the cursor should be moved forward
     * @return void
     */
    public static function moveCursorForward($steps = 1)
    {
        self::writeControlSequence("\033[" . (int) $steps . 'C');
    }

    /**
     * Moves the terminal cursor backward by sending ANSI control code CUB to the terminal.
     *
     * If the cursor is already at the edge of the screen, this has no effect.
     *
     * @param int $steps number of steps the cursor should be moved backward
     * @return void
     */
    public static function moveCursorBackward($steps = 1)
    {
        self::writeControlSequence("\033[" . (int) $steps . 'D');
    }

    /**
     * Moves the terminal cursor to the beginning of the next line by sending ANSI control code CNL.
     *
     * @param int $lines number of lines the cursor should be moved down
     * @return void
     */
    public static function moveCursorNextLine($lines = 1)
    {
        self::writeControlSequence("\033[" . (int) $lines . 'E');
    }

    /**
     * Moves the terminal cursor to the beginning of the previous line by sending ANSI control code CPL.
     *
     * @param int $lines number of lines the cursor should be moved up
     * @return void
     */
    public static function moveCursorPrevLine($lines = 1)
    {
        self::writeControlSequence("\033[" . (int) $lines . 'F');
    }

    /**
     * Moves the cursor to an absolute position by sending ANSI control code CUP or CHA to the terminal.
     *
     * @param int $column 1-based column number, 1 is the left edge of the screen.
     * @param int|null $row 1-based row number, 1 is the top edge of the screen; null moves the cursor only
     *     within the current line.
     * @return void
     */
    public static function moveCursorTo($column, $row = null)
    {
        if ($row === null) {
            self::writeControlSequence("\033[" . (int) $column . 'G');

            return;
        }

        self::writeControlSequence("\033[" . (int) $row . ';' . (int) $column . 'H');
    }

    /**
     * Scrolls whole page up by sending ANSI control code SU to the terminal.
     *
     * New lines are added at the bottom. This is not supported by ANSI.SYS used in windows.
     *
     * @param int $lines number of lines to scroll up
     * @return void
     */
    public static function scrollUp($lines = 1)
    {
        self::writeControlSequence("\033[" . (int) $lines . 'S');
    }

    /**
     * Scrolls whole page down by sending ANSI control code SD to the terminal.
     *
     * New lines are added at the top. This is not supported by ANSI.SYS used in windows.
     *
     * @param int $lines number of lines to scroll down
     * @return void
     */
    public static function scrollDown($lines = 1)
    {
        self::writeControlSequence("\033[" . (int) $lines . 'T');
    }

    /**
     * Saves the current cursor position by sending ANSI control code SCP to the terminal.
     *
     * The position can then be restored with restoreCursorPosition().
     *
     * @return void
     */
    public static function saveCursorPosition()
    {
        self::writeControlSequence("\033[s");
    }

    /**
     * Restores the cursor position saved with saveCursorPosition() by sending ANSI control code RCP.
     *
     * @return void
     */
    public static function restoreCursorPosition()
    {
        self::writeControlSequence("\033[u");
    }

    /**
     * Hides the cursor by sending ANSI DECTCEM code ?25l to the terminal.
     *
     * Use showCursor() to bring it back. Show the cursor again before the application exits, or it may stay
     * hidden in the terminal.
     *
     * @return void
     */
    public static function hideCursor()
    {
        self::writeControlSequence("\033[?25l");
    }

    /**
     * Shows the cursor hidden by hideCursor() by sending ANSI DECTCEM code ?25h to the terminal.
     *
     * @return void
     */
    public static function showCursor()
    {
        self::writeControlSequence("\033[?25h");
    }

    /**
     * Clears entire screen content by sending ANSI control code ED with argument 2 to the terminal.
     *
     * Cursor position will not be changed.
     * **Note:** ANSI.SYS implementation used in windows will reset cursor position to upper left corner of the screen.
     *
     * @return void
     */
    public static function clearScreen()
    {
        self::writeControlSequence("\033[2J");
    }

    /**
     * Clears text from cursor to the beginning of the screen by sending ANSI control code ED with argument 1.
     *
     * Cursor position will not be changed.
     *
     * @return void
     */
    public static function clearScreenBeforeCursor()
    {
        self::writeControlSequence("\033[1J");
    }

    /**
     * Clears text from cursor to the end of the screen by sending ANSI control code ED with argument 0.
     *
     * Cursor position will not be changed.
     *
     * @return void
     */
    public static function clearScreenAfterCursor()
    {
        self::writeControlSequence("\033[0J");
    }

    /**
     * Clears the line the cursor is on by sending ANSI control code EL with argument 2 to the terminal.
     *
     * Cursor position will not be changed.
     *
     * @return void
     */
    public static function clearLine()
    {
        self::writeControlSequence("\033[2K");
    }

    /**
     * Clears text from cursor position to the beginning of the line by sending ANSI control code EL with argument 1.
     *
     * Cursor position will not be changed.
     *
     * @return void
     */
    public static function clearLineBeforeCursor()
    {
        self::writeControlSequence("\033[1K");
    }

    /**
     * Clears text from cursor position to the end of the line by sending ANSI control code EL with argument 0.
     *
     * Cursor position will not be changed.
     *
     * @return void
     */
    public static function clearLineAfterCursor()
    {
        self::writeControlSequence("\033[0K");
    }

    /**
     * Returns the ANSI format code.
     *
     * @param array<int|string> $format Formatting values: any of the `FG_*`, `BG_*` and style constants,
     *     and the results of xtermFgColor() and xtermBgColor().
     * @return string The ANSI format code according to the given formatting constants.
     */
    public static function ansiFormatCode($format)
    {
        return "\033[" . implode(';', $format) . 'm';
    }

    /**
     * Echoes an ANSI format code that affects the formatting of any text that is printed afterwards.
     *
     * @param array<int|string> $format Formatting values: any of the `FG_*`, `BG_*` and style constants,
     *     and the results of xtermFgColor() and xtermBgColor().
     * @return void
     * @see ansiFormatCode()
     * @see endAnsiFormat()
     */
    public static function beginAnsiFormat($format)
    {
        self::writeControlSequence("\033[" . implode(';', $format) . 'm');
    }

    /**
     * Resets any ANSI format set by a previous beginAnsiFormat() call.
     *
     * Any output after this will have the default text format. This is equal to echoing
     * `ConsoleHelper::ansiFormatCode([ConsoleHelper::RESET])`.
     *
     * @return void
     */
    public static function endAnsiFormat()
    {
        self::writeControlSequence("\033[0m");
    }

    /**
     * Returns a string formatted with the given ANSI style.
     *
     * @param string $string the string to be formatted
     * @param array<int|string> $format Formatting values: any of the `FG_*`, `BG_*` and style constants,
     *     and the results of xtermFgColor() and xtermBgColor().
     * @return string
     */
    public static function ansiFormat($string, $format = [])
    {
        $code = implode(';', $format);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . 'm' : '') . $string . "\033[0m";
    }

    /**
     * Returns the ANSI format code for an xterm foreground color.
     *
     * Pass the return value to ansiFormat(), ansiFormatCode() or beginAnsiFormat().
     *
     * @param int $colorCode xterm color code
     * @return string
     * @see http://en.wikipedia.org/wiki/Talk:ANSI_escape_code#xterm-256colors
     */
    public static function xtermFgColor($colorCode)
    {
        return '38;5;' . $colorCode;
    }

    /**
     * Returns the ANSI format code for an xterm background color.
     *
     * Pass the return value to ansiFormat(), ansiFormatCode() or beginAnsiFormat().
     *
     * @param int $colorCode xterm color code
     * @return string
     * @see http://en.wikipedia.org/wiki/Talk:ANSI_escape_code#xterm-256colors
     */
    public static function xtermBgColor($colorCode)
    {
        return '48;5;' . $colorCode;
    }

    /**
     * Strips ANSI control codes from a string.
     *
     * @param string $string String to strip
     * @return string
     */
    public static function stripAnsiFormat($string)
    {
        return (string) preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }

    /**
     * Returns the length of the string without ANSI color codes.
     *
     * @param string $string the string to measure
     * @return int the length of the string not counting ANSI format characters
     */
    public static function ansiStrlen($string)
    {
        return mb_strlen(static::stripAnsiFormat($string));
    }

    /**
     * Converts an ANSI formatted string to HTML.
     *
     * Note: xTerm 256 bit colors are currently not supported.
     *
     * @param string $string the string to convert.
     * @param array<int,array<string,string|list<string>>> $styleMap Additional CSS styles keyed by ANSI
     *     control code (`FG_*`, `BG_*`, style constants); each maps CSS property names to a value or a list of
     *     values joined with spaces. Codes with a built-in style keep the built-in one.
     * @return string HTML representation of the ANSI formatted string
     */
    public static function ansiToHtml($string, $styleMap = [])
    {
        $styleMap = [
                        // http://www.w3.org/TR/CSS2/syndata.html#value-def-color
                        self::FG_BLACK    => ['color' => 'black'],
                        self::FG_BLUE     => ['color' => 'blue'],
                        self::FG_CYAN     => ['color' => 'aqua'],
                        self::FG_GREEN    => ['color' => 'lime'],
                        self::FG_GREY     => ['color' => 'silver'],
                        // http://meyerweb.com/eric/thoughts/2014/06/19/rebeccapurple/
                        // http://dev.w3.org/csswg/css-color/#valuedef-rebeccapurple
                        self::FG_PURPLE   => ['color' => 'rebeccapurple'],
                        self::FG_RED      => ['color' => 'red'],
                        self::FG_YELLOW   => ['color' => 'yellow'],
                        self::BG_BLACK    => ['background-color' => 'black'],
                        self::BG_BLUE     => ['background-color' => 'blue'],
                        self::BG_CYAN     => ['background-color' => 'aqua'],
                        self::BG_GREEN    => ['background-color' => 'lime'],
                        self::BG_GREY     => ['background-color' => 'silver'],
                        self::BG_PURPLE   => ['background-color' => 'rebeccapurple'],
                        self::BG_RED      => ['background-color' => 'red'],
                        self::BG_YELLOW   => ['background-color' => 'yellow'],
                        self::BOLD        => ['font-weight' => 'bold'],
                        self::ITALIC      => ['font-style' => 'italic'],
                        self::UNDERLINE   => ['text-decoration' => ['underline']],
                        self::OVERLINED   => ['text-decoration' => ['overline']],
                        self::CROSSED_OUT => ['text-decoration' => ['line-through']],
                        self::BLINK       => ['text-decoration' => ['blink']],
                        self::CONCEALED   => ['visibility' => 'hidden'],
                    ] + $styleMap;
        $tags     = 0;
        $result   = (string) preg_replace_callback(
            '/\033\[([\d;]+)m/',
            function (array $ansi) use (&$tags, $styleMap): string {
                $style    = [];
                $reset    = false;
                $negative = false;
                foreach (explode(';', $ansi[1]) as $controlCode) {
                    if ($controlCode == 0) {
                        $style = [];
                        $reset = true;
                    } elseif ($controlCode == self::NEGATIVE) {
                        $negative = true;
                    } elseif (isset($styleMap[$controlCode])) {
                        $style[] = $styleMap[$controlCode];
                    }
                }
                $return = '';
                while ($reset && $tags > 0) {
                    $return .= '</span>';
                    $tags--;
                }
                if (empty($style)) {
                    return $return;
                }
                $currentStyle = [];
                foreach ($style as $content) {
                    $currentStyle = ArrayHelper::merge($currentStyle, $content);
                }
                // if negative is set, invert background and foreground
                if ($negative) {
                    if (isset($currentStyle['color'])) {
                        $fgColor = $currentStyle['color'];
                        unset($currentStyle['color']);
                    }
                    if (isset($currentStyle['background-color'])) {
                        $bgColor = $currentStyle['background-color'];
                        unset($currentStyle['background-color']);
                    }
                    if (isset($fgColor)) {
                        $currentStyle['background-color'] = $fgColor;
                    }
                    if (isset($bgColor)) {
                        $currentStyle['color'] = $bgColor;
                    }
                }
                $styleString = '';
                foreach ($currentStyle as $name => $value) {
                    // a list of values is joined with spaces
                    $values = is_array($value) ? $value : [$value];
                    $styleString .= $name . ': ' . implode(' ', array_map(self::toText(...), $values)) . ';';
                }
                $tags++;

                return "$return<span style=\"$styleString\">";
            },
            $string
        );
        while ($tags > 0) {
            $result .= '</span>';
            $tags--;
        }

        return $result;
    }

    /**
     * Converts a string to ANSI formatted text by replacing patterns like %y (for yellow) with ANSI control codes.
     *
     * Uses almost the same syntax as https://github.com/pear/Console_Color2/blob/master/Console/Color2.php
     * The conversion table is: ('bold' meaning 'light' on some
     * terminals). It's almost the same conversion table irssi uses.
     * <pre>
     *                  text      text            background
     *      ------------------------------------------------
     *      %k %K %0    black     dark grey       black
     *      %r %R %1    red       bold red        red
     *      %g %G %2    green     bold green      green
     *      %y %Y %3    yellow    bold yellow     yellow
     *      %b %B %4    blue      bold blue       blue
     *      %m %M %5    magenta   bold magenta    magenta
     *      %p %P       magenta (think: purple)
     *      %c %C %6    cyan      bold cyan       cyan
     *      %w %W %7    white     bold white      white
     *
     *      %F     Blinking, Flashing
     *      %U     Underline
     *      %8     Reverse
     *      %_,%9  Bold
     *
     *      %n     Resets the color
     *      %%     A single %
     * </pre>
     * First param is the string to convert, second is an optional flag if
     * colors should be used. It defaults to true, if set to false, the
     * color codes will just be removed (And %% will be transformed into %)
     *
     * @param string $string String to convert
     * @param bool $colored Should the string be colored?
     * @return string
     */
    public static function renderColoredString($string, $colored = true)
    {
        if (!$colored) {
            return (string) preg_replace('/%((%)|.)/', '$2', $string);
        }

        $string = str_replace('%%', '% ', $string);
        foreach (self::COLOR_CONVERSIONS as $key => $value) {
            $string = str_replace(
                $key,
                static::ansiFormatCode($value),
                $string
            );
        }

        return str_replace('% ', '%', $string);
    }

    /**
     * Escapes % so they don't get interpreted as color codes by renderColoredString().
     *
     * @param string $string String to escape
     * @return string
     */
    public static function escape($string)
    {
        return str_replace('%', '%%', $string);
    }

    /**
     * Returns true if the stream supports colorization.
     *
     * ANSI colors are not supported on windows without ansicon, nor on a stream that is not a terminal.
     *
     * @param resource|int $stream an open stream, or a file descriptor number
     * @return bool true if the stream supports ANSI colors, otherwise false.
     */
    public static function streamSupportsAnsiColors($stream)
    {
        return DIRECTORY_SEPARATOR === '\\'
            ? getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON'
            : function_exists('posix_isatty')
                && (is_resource($stream) ? stream_isatty($stream) : is_int($stream) && posix_isatty($stream));
    }

    /**
     * Returns true if the console is running on windows.
     *
     * @return bool
     */
    public static function isRunningOnWindows()
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Returns terminal screen size.
     *
     * Usage:
     *
     * ```php
     * [$width, $height] = ConsoleHelper::getScreenSize();
     * ```
     *
     * @param bool $refresh whether to force checking and not re-use cached size value.
     *     This is useful to detect changing window size while the application is running but may
     *     not get up to date values on every terminal.
     * @return array{int, int}|false The width and the height, or false when the size could not be determined.
     */
    public static function getScreenSize($refresh = false)
    {
        if (self::$screenSize !== null && !$refresh) {
            return self::$screenSize;
        }
        if (static::isRunningOnWindows()) {
            $output = [];
            exec('mode con', $output);
            if (isset($output[1]) && strpos($output[1], 'CON') !== false) {
                return self::$screenSize = [
                    (int) preg_replace('~\D~', '', $output[4]),
                    (int) preg_replace('~\D~', '', $output[3])
                ];
            }
        } else {
            // try stty if available
            $stty = [];
            if (exec('stty -a 2>&1', $stty)) {
                $stty = implode(' ', $stty);
                // Linux stty output
                if (preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', $stty, $matches)) {
                    return self::$screenSize = [(int) $matches[2], (int) $matches[1]];
                }
                // MacOS stty output
                if (preg_match('/(\d+)\s+rows;\s*(\d+)\s+columns;/mi', $stty, $matches)) {
                    return self::$screenSize = [(int) $matches[2], (int) $matches[1]];
                }
            }
            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int) exec('tput cols 2>&1')) > 0 && ($height = (int) exec('tput lines 2>&1')) > 0) {
                return self::$screenSize = [$width, $height];
            }
            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int) getenv('COLUMNS')) > 0 && ($height = (int) getenv('LINES')) > 0) {
                return self::$screenSize = [$width, $height];
            }
        }

        return self::$screenSize = false;
    }

    /**
     * Word wrap text with indentation to fit the screen size.
     *
     * If screen size could not be detected, or the indentation is greater than the screen size, the text will not be
     * wrapped.
     *
     * The first line will **not** be indented, so `ConsoleHelper::wrapText("Lorem ipsum dolor sit amet.", 4)` will
     * result in the following output, given the screen width is 16 characters:
     *
     * ```
     * Lorem ipsum
     *     dolor sit
     *     amet.
     * ```
     *
     * @param string $text the text to be wrapped
     * @param int $indent number of spaces to use for indentation.
     * @param bool $refresh whether to force refresh of screen size; passed to getScreenSize().
     * @return string the wrapped text.
     */
    public static function wrapText($text, $indent = 0, $refresh = false)
    {
        $size = static::getScreenSize($refresh);
        if ($size === false || $size[0] <= $indent) {
            return $text;
        }
        $pad   = str_repeat(' ', $indent);
        $lines = explode("\n", wordwrap($text, $size[0] - $indent, "\n", true));
        $first = true;
        foreach ($lines as $i => $line) {
            if ($first) {
                $first = false;
                continue;
            }
            $lines[$i] = $pad . $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Gets input from STDIN and returns a string right-trimmed for EOLs.
     *
     * @param bool $raw If set to true, returns the raw line without trimming, or false at the end of input
     * @return ($raw is true ? string|false : string) the string read from stdin
     */
    public static function stdin($raw = false)
    {
        return $raw ? fgets(\STDIN) : rtrim((string) fgets(\STDIN), PHP_EOL);
    }

    /**
     * Prints a string to STDOUT.
     *
     * @param string $string the string to print
     * @return int|false Number of bytes printed or false on error
     */
    public static function stdout($string)
    {
        return fwrite(\STDOUT, $string);
    }

    /**
     * Prints a string to STDERR.
     *
     * @param string $string the string to print
     * @return int|false Number of bytes printed or false on error
     */
    public static function stderr($string)
    {
        return fwrite(\STDERR, $string);
    }

    /**
     * Asks the user for input and returns it once the user types a carriage return (PHP_EOL).
     *
     * @param string|null $prompt the prompt to display before waiting for input (optional)
     * @return string the user's input
     */
    public static function input($prompt = null)
    {
        if (isset($prompt)) {
            static::stdout($prompt);
        }

        return static::stdin();
    }

    /**
     * Prints text to STDOUT appended with a carriage return (PHP_EOL).
     *
     * @param string|null $string the text to print
     * @return int|false number of bytes printed or false on error.
     */
    public static function output($string = null)
    {
        return static::stdout($string . PHP_EOL);
    }

    /**
     * Prints text to STDERR appended with a carriage return (PHP_EOL).
     *
     * @param string|null $string the text to print
     * @return int|false number of bytes printed or false on error.
     */
    public static function error($string = null)
    {
        return static::stderr($string . PHP_EOL);
    }

    /**
     * Prompts the user for input until it validates, and returns it.
     *
     * Options:
     * - `required`: whether an empty answer is refused
     * - `default`: the value returned when the user enters nothing
     * - `pattern`: regular expression the input must match
     * - `validator`: a callable that receives the input and an error variable by reference, and returns
     *   whether the input is valid; the error it sets is printed instead of `error`
     * - `error`: the message printed when the input is refused
     *
     * @param string $text prompt string
     * @param PromptOptions $options the options to validate the input
     * @return string the user input
     */
    public static function prompt($text, $options = [])
    {
        $options += [
            'required'  => false,
            'default'   => null,
            'pattern'   => null,
            'validator' => null,
            'error'     => 'Invalid input.',
        ];
        $error = null;
        while (true) {
            $input = $options['default']
                ? static::input("$text [" . $options['default'] . '] ')
                : static::input("$text ");
            if ($input === '') {
                if (isset($options['default'])) {
                    return $options['default'];
                }
                if (!$options['required']) {
                    return $input;
                }
                static::output($options['error']);
                continue;
            }
            if ($options['pattern'] && !preg_match($options['pattern'], $input)) {
                static::output($options['error']);
                continue;
            }
            if ($options['validator'] && !$options['validator']($input, $error)) {
                static::output(isset($error) ? self::toText($error) : $options['error']);
                continue;
            }

            return $input;
        }
    }

    /**
     * Asks user to confirm by typing y or n.
     *
     * A typical usage looks like the following:
     *
     * ```php
     * if (ConsoleHelper::confirm("Are you sure?")) {
     *     echo "user typed yes\n";
     * } else {
     *     echo "user typed no\n";
     * }
     * ```
     *
     * @param string $message to print out before waiting for user input
     * @param bool $default this value is returned if no selection is made.
     * @return bool whether user confirmed
     */
    public static function confirm($message, $default = false)
    {
        while (true) {
            static::stdout($message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:');
            $input = trim(static::stdin());
            if (empty($input)) {
                return $default;
            }
            if (!strcasecmp($input, 'y') || !strcasecmp($input, 'yes')) {
                return true;
            }
            if (!strcasecmp($input, 'n') || !strcasecmp($input, 'no')) {
                return false;
            }
        }
    }

    /**
     * Asks the user to choose one of the options and returns the chosen key.
     *
     * Entering '?' lists the options with their explanations and asks again; any other input that is not
     * an option key asks again.
     *
     * @param string $prompt the prompt message
     * @param array<int|string,string> $options Key-value array of options to choose from. Key is what is
     *     entered and returned, value is what the help lists for it.
     * @return string An option character the user chose
     */
    public static function select($prompt, $options = [])
    {
        while (true) {
            static::stdout("$prompt [" . implode(',', array_keys($options)) . ',?]: ');
            $input = static::stdin();
            if ($input === '?') {
                foreach ($options as $key => $value) {
                    static::output(" $key - $value");
                }
                static::output(' ? - Show help');
                continue;
            }
            if (array_key_exists($input, $options)) {
                return $input;
            }
        }
    }

    /**
     * Starts display of a progress bar on screen.
     *
     * This bar will be updated by updateProgress() and may be ended by endProgress().
     *
     * The following example shows a simple usage of a progress bar:
     *
     * ```php
     * ConsoleHelper::startProgress(0, 1000);
     * for ($n = 1; $n <= 1000; $n++) {
     *     usleep(1000);
     *     ConsoleHelper::updateProgress($n, 1000);
     * }
     * ConsoleHelper::endProgress();
     * ```
     *
     * Git clone like progress (showing only status information):
     * ```php
     * ConsoleHelper::startProgress(0, 1000, 'Counting objects: ', false);
     * for ($n = 1; $n <= 1000; $n++) {
     *     usleep(1000);
     *     ConsoleHelper::updateProgress($n, 1000);
     * }
     * ConsoleHelper::endProgress("done." . PHP_EOL);
     * ```
     *
     * @param int $done the number of items that are completed.
     * @param int $total the total value of items that are to be done.
     * @param string $prefix an optional string to display before the progress bar.
     *     Default to empty string which results in no prefix to be displayed.
     * @param int|float|false|null $width optional width of the progressbar. This can be an integer representing
     *     the number of characters to display for the progress bar or a float between 0 and 1
     *     representing the percentage of screen with the progress bar may take. It can also be set
     *     to false to disable the bar and only show progress information like percent, number of
     *     items and ETA. If not set, the bar will be as wide as the screen. Screen size will be
     *     detected using getScreenSize().
     * @return void
     * @see updateProgress
     * @see endProgress
     */
    public static function startProgress($done, $total, $prefix = '', $width = null)
    {
        self::$_progressStart         = time();
        self::$_progressWidth         = $width;
        self::$_progressPrefix        = $prefix;
        self::$_progressEta           = null;
        self::$_progressEtaLastDone   = 0;
        self::$_progressEtaLastUpdate = time();
        static::updateProgress($done, $total);
    }

    /**
     * Updates a progress bar that has been started by startProgress().
     *
     * @param int $done the number of items that are completed.
     * @param int $total the total value of items that are to be done.
     * @param string|null $prefix an optional string to display before the progress bar.
     *     Defaults to null meaning the prefix specified by startProgress() will be used.
     *     If prefix is specified it will update the prefix that will be used by later calls.
     * @return void
     * @see startProgress
     * @see endProgress
     */
    public static function updateProgress($done, $total, $prefix = null)
    {
        if ($prefix === null) {
            $prefix = self::$_progressPrefix;
        } else {
            self::$_progressPrefix = $prefix;
        }
        $width   = self::getProgressbarWidth($prefix);
        $percent = ($total == 0) ? 1 : $done / $total;
        $info    = sprintf('%d%% (%d/%d)', $percent * 100, $done, $total);
        self::setETA($done, $total);
        $info .= self::$_progressEta === null ? ' ETA: n/a' : sprintf(' ETA: %d sec.', self::$_progressEta);
        // Number extra characters outputted. These are opening [, closing ], and space before info
        // Since Windows uses \r\n\ for line endings, there's one more in the case
        $extraChars = static::isRunningOnWindows() ? 4 : 3;
        $width      -= $extraChars + static::ansiStrlen($info);
        // skipping progress bar on very small display or if forced to skip
        if ($width < 5) {
            static::stdout("\r$prefix$info   ");
        } else {
            if ($percent < 0) {
                $percent = 0;
            } elseif ($percent > 1) {
                $percent = 1;
            }
            $bar    = (int) floor($percent * $width);
            $status = str_repeat('=', $bar);
            if ($bar < $width) {
                $status .= '>';
                $status .= str_repeat(' ', (int) ($width - $bar - 1));
            }
            static::stdout("\r$prefix" . "[$status] $info");
        }
        flush();
    }

    /**
     * Ends a progress bar that has been started by startProgress().
     *
     * @param string|bool $remove This can be `false` to leave the progress bar on screen and just print a newline.
     *     If set to `true`, the line of the progress bar will be cleared. This may also be
     *     a string to be displayed instead of the progress bar.
     * @param bool $keepPrefix whether to keep the prefix that has been specified for the progressbar when
     *     progressbar gets removed. Defaults to true.
     * @return void
     * @see startProgress
     * @see updateProgress
     */
    public static function endProgress($remove = false, $keepPrefix = true)
    {
        if ($remove === false) {
            static::stdout(PHP_EOL);
        } else {
            if (static::streamSupportsAnsiColors(\STDOUT)) {
                static::clearLine();
            }
            static::stdout(
                "\r" . ($keepPrefix ? self::$_progressPrefix : '') . (is_string($remove) ? $remove : '')
            );
        }
        flush();
        self::$_progressStart         = null;
        self::$_progressWidth         = null;
        self::$_progressPrefix        = '';
        self::$_progressEta           = null;
        self::$_progressEtaLastDone   = 0;
        self::$_progressEtaLastUpdate = null;
    }

    /**
     * Returns the width available to the progress bar after the prefix.
     *
     * @param string $prefix the string displayed before the progress bar.
     * @return int|float the width in characters
     */
    private static function getProgressbarWidth(string $prefix): int|float
    {
        $width = self::$_progressWidth;
        if ($width === false) {
            return 0;
        }
        $screenSize = static::getScreenSize(true);
        if ($screenSize === false) {
            if ($width < 1) {
                return 0;
            }
        } elseif ($width === null) {
            $width = $screenSize[0];
        } elseif ($width > 0 && $width < 1) {
            $width = floor($screenSize[0] * $width);
        }

        return $width - static::ansiStrlen($prefix);
    }

    /**
     * Recalculates the estimated time left of the progress bar from the items done since the last estimate.
     *
     * @param int $done the number of items that are completed.
     * @param int $total the total value of items that are to be done.
     * @return void
     */
    private static function setETA($done, $total): void
    {
        if ($done > $total || $done == 0) {
            self::$_progressEta           = null;
            self::$_progressEtaLastUpdate = time();

            return;
        }
        if ($done < $total
            && time() - (int) self::$_progressEtaLastUpdate > 1
            && $done > self::$_progressEtaLastDone
        ) {
            $since = (int) (self::$_progressEtaLastUpdate ?: self::$_progressStart);
            $rate  = (time() - $since) / ($done - self::$_progressEtaLastDone);
            self::$_progressEta           = $rate * ($total - $done);
            self::$_progressEtaLastUpdate = time();
            self::$_progressEtaLastDone   = $done;
        }
    }

    /**
     * Writes an ANSI control sequence to the output, where the terminal interprets it.
     *
     * @param string $sequence the control sequence
     * @return void
     */
    private static function writeControlSequence(string $sequence): void
    {
        echo $sequence;
    }

    /**
     * Converts a scalar or a stringable object to its text; any other value has no text.
     *
     * @param mixed $value the value to print
     * @return string
     */
    private static function toText(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
