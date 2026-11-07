<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Debug;

use Hryvinskyi\Base\Exception\InvalidValueException;
use Hryvinskyi\Base\Model\Debug\ClosureSourceReader;
use Hryvinskyi\Base\Model\Debug\PhpTokenHighlighter;
use Hryvinskyi\Base\Model\Debug\VarDumper;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\CustomDebugInfo;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\InvalidDebugInfo;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\VarDumperArrayable;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\VarDumperIterable;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\VarDumperStringable;
use Hryvinskyi\Base\Test\Unit\Model\Debug\Fixture\VarDumperUnserializable;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\ZendEscaper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class VarDumperTest extends TestCase
{
    /**
     * Markup the highlighted dump adds around the text: the wrapper and the colour spans.
     */
    private const HIGHLIGHT_MARKUP = '#<pre><code style="color: \#[0-9A-F]{6}">|</code></pre>'
        . '|<span style="color: \#[0-9A-F]{6}">|</span>#';

    /**
     * The dumper under test, built on the real file driver, serializer and escaper.
     *
     * @var VarDumper
     */
    private VarDumper $dumper;

    /**
     * Builds the dumper.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->dumper = $this->createDumper(new File());
    }

    /**
     * An object of a class that does not exist dumps as an incomplete class naming the missing class.
     *
     * @return void
     */
    public function testDumpIncompleteObject(): void
    {
        $incompleteObj = (new Serialize())->unserialize('O:16:"nonExistingClass":0:{}');
        $dumpResult = $this->dumper->dumpAsString($incompleteObj);
        $this->assertStringContainsString("__PHP_Incomplete_Class#1\n(", $dumpResult);
        $this->assertStringContainsString('nonExistingClass', $dumpResult);
    }

    /**
     * An object of a class that does not exist exports with the name of the missing class.
     *
     * @return void
     */
    public function testExportIncompleteObject(): void
    {
        $incompleteObj = (new Serialize())->unserialize('O:16:"nonExistingClass":0:{}');
        $exportResult = $this->dumper->export($incompleteObj);
        $this->assertStringContainsString('nonExistingClass', $exportResult);
    }

    /**
     * An object dumps with its class, its number in the dump and its properties.
     *
     * @return void
     */
    public function testDumpObject(): void
    {
        $obj = new \stdClass();
        $this->assertEquals("stdClass#1\n(\n)", $this->dumper->dumpAsString($obj));
        $obj = new \stdClass();
        $obj->name = 'test-name';
        $obj->price = 19;
        $dumpResult = $this->dumper->dumpAsString($obj);
        $this->assertStringContainsString("stdClass#1\n(", $dumpResult);
        $this->assertStringContainsString("[name] => 'test-name'", $dumpResult);
        $this->assertStringContainsString('[price] => 19', $dumpResult);
    }

    /**
     * Data provider for testDumpValue().
     *
     * @return array<string, array{mixed, int, string}>
     */
    public static function dataProviderDump(): array
    {
        return [
            'true' => [true, 10, 'true'],
            'false' => [false, 10, 'false'],
            'integer' => [75, 10, '75'],
            'float' => [7.5, 10, '7.5'],
            'null' => [null, 10, 'null'],
            'quotes, backslash and NUL are escaped' => ["a'b\"c\\d\0e", 10, "'a\\'b\\\"c\\\\d\\0e'"],
            'empty array' => [[], 10, '[]'],
            'nested array' => [
                ['key' => [1]],
                10,
                "[\n    'key' => [\n        0 => 1\n    ]\n]",
            ],
            'array beyond the depth' => [['key' => [1]], 1, "[\n    'key' => [...]\n]"],
            'object beyond the depth' => [[new \stdClass()], 1, "[\n    0 => stdClass(...)\n]"],
        ];
    }

    /**
     * Each kind of value dumps in its readable form, down to the requested depth.
     *
     * @param mixed $var value to dump
     * @param int $depth maximum depth of the dump
     * @param string $expected expected dump
     * @return void
     */
    #[DataProvider('dataProviderDump')]
    public function testDumpValue(mixed $var, int $depth, string $expected): void
    {
        $this->assertSame($expected, $this->dumper->dumpAsString($var, $depth));
    }

    /**
     * A resource dumps as a placeholder.
     *
     * @return void
     */
    public function testDumpResource(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);
        $this->assertSame('{resource}', $this->dumper->dumpAsString($resource));
        fclose($resource);
    }

    /**
     * An object met a second time dumps as a reference to its first occurrence.
     *
     * @return void
     */
    public function testDumpRepeatedObjectAsReference(): void
    {
        $obj = new \stdClass();
        $this->assertSame(
            "[\n    0 => stdClass#1\n    (\n    )\n    1 => stdClass#1(...)\n]",
            $this->dumper->dumpAsString([$obj, $obj])
        );
    }

    /**
     * Each dump numbers its objects from one, whatever an earlier dump met.
     *
     * @return void
     */
    public function testEachDumpNumbersObjectsAfresh(): void
    {
        $first = new \stdClass();
        $second = new \stdClass();
        $this->dumper->dumpAsString([$first, $second]);

        $this->assertSame("stdClass#1\n(\n)", $this->dumper->dumpAsString($second));
    }

    /**
     * The highlighted dump is HTML without the PHP open tag used for highlighting.
     *
     * @return void
     */
    public function testDumpHighlighted(): void
    {
        $dumpResult = $this->dumper->dumpAsString(75, 10, true);
        $this->assertStringContainsString('75', $dumpResult);
        $this->assertStringContainsString('<code', $dumpResult);
        $this->assertStringNotContainsString('&lt;?php', $dumpResult);
    }

    /**
     * Removing the highlighting markup and decoding the entities gives back the plain dump, even where the dump
     * is not valid PHP.
     *
     * @return void
     */
    public function testHighlightedDumpReadsAsThePlainDump(): void
    {
        $object = new \stdClass();
        $object->label = "it's \"quoted\" & <b>bold</b>";
        $object->self = $object;
        $object->list = [1, 2.5, true, null, '?>', '/* not a comment'];
        $value = ['object' => $object, 'again' => $object, "key\0with NUL" => '#'];

        $plain = $this->dumper->dumpAsString($value);
        $highlighted = $this->dumper->dumpAsString($value, 10, true);

        $this->assertNotSame($plain, $highlighted);
        $this->assertSame($plain, htmlspecialchars_decode($this->stripHighlightMarkup($highlighted), ENT_QUOTES));
    }

    /**
     * Invalid UTF-8 bytes are the one difference between the highlighted text and the plain dump.
     *
     * @return void
     */
    public function testHighlightedDumpSubstitutesInvalidUtf8(): void
    {
        $text = $this->stripHighlightMarkup($this->dumper->dumpAsString("a\x80", 10, true));

        $this->assertSame(
            str_replace("\x80", "\u{FFFD}", $this->dumper->dumpAsString("a\x80")),
            htmlspecialchars_decode($text, ENT_QUOTES)
        );
    }

    /**
     * Text from the dumped value never reaches the highlighted HTML as markup.
     *
     * @return void
     */
    public function testHighlightedDumpEscapesTheDumpedValue(): void
    {
        $highlighted = $this->dumper->dumpAsString(
            ['comment' => '<script>alert(1)</script>', 'name' => '"><img onerror=x>'],
            10,
            true
        );
        $text = $this->stripHighlightMarkup($highlighted);

        $this->assertStringNotContainsString('<', $text);
        $this->assertStringNotContainsString('>', $text);
        $this->assertStringNotContainsString('"', $text);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $text);
        $this->assertStringContainsString('&quot;&gt;&lt;img onerror=x&gt;', $text);
    }

    /**
     * Data provider for testExport().
     *
     * @return list<array{mixed, string}>
     */
    public static function dataProviderExport(): array
    {
        // Regular :
        $data = [
            [
                'test string',
                var_export('test string', true),
            ],
            [
                75,
                var_export(75, true),
            ],
            [
                7.5,
                var_export(7.5, true),
            ],
            [
                null,
                'null',
            ],
            [
                true,
                'true',
            ],
            [
                false,
                'false',
            ],
            [
                [],
                '[]',
            ],
        ];
        // Arrays :
        $var = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $expectedResult = <<<'RESULT'
[
    'key1' => 'value1',
    'key2' => 'value2',
]
RESULT;
        $data[] = [$var, $expectedResult];
        $var = [
            'value1',
            'value2',
        ];
        $expectedResult = <<<'RESULT'
[
    'value1',
    'value2',
]
RESULT;
        $data[] = [$var, $expectedResult];
        $var = [
            'key1' => [
                'subkey1' => 'value2',
            ],
            'key2' => [
                'subkey2' => 'value3',
            ],
        ];
        $expectedResult = <<<'RESULT'
[
    'key1' => [
        'subkey1' => 'value2',
    ],
    'key2' => [
        'subkey2' => 'value3',
    ],
]
RESULT;
        $data[] = [$var, $expectedResult];
        // Objects :
        $var = new \stdClass();
        $var->testField = 'Test Value';
        $expectedResult = 'unserialize(\'O:8:"stdClass":1:{s:9:"testField";s:10:"Test Value";}\')';
        $data[] = [$var, $expectedResult];
        // A closure exports as its source, exactly as written, from `function` to its closing brace:
        $var = function () {
            return 2;
        };
        $expectedResult = <<<'RESULT'
function () {
            return 2;
        }
RESULT;
        $data[] = [$var, $expectedResult];
        return $data;
    }

    /**
     * Each kind of value exports as a PHP expression.
     *
     * @param mixed $var value to export
     * @param string $expectedResult expected PHP expression
     * @return void
     */
    #[DataProvider('dataProviderExport')]
    public function testExport(mixed $var, string $expectedResult): void
    {
        $exportResult = $this->dumper->export($var);
        $expected = str_replace("\r\n", "\n", $expectedResult);
        $actual = str_replace("\r\n", "\n", $exportResult);
        $this->assertEquals($expected, $actual);
    }

    /**
     * An object that cannot be serialized still exports, including one that references itself.
     *
     * @return void
     */
    #[Depends('testExport')]
    public function testExportObjectFallback(): void
    {
        $var = new \stdClass();
        $var->testFunction = function () {
            return 2;
        };
        $exportResult = $this->dumper->export($var);
        $this->assertNotEmpty($exportResult);
        $master = new \stdClass();
        $slave = new \stdClass();
        $master->slave = $slave;
        $slave->master = $master;
        $master->function = function () {
            return true;
        };
        $exportResult = $this->dumper->export($master);
        $this->assertNotEmpty($exportResult);
    }

    /**
     * An unserializable object with __toString() exports as its string.
     *
     * @return void
     */
    public function testExportUnserializableStringableObject(): void
    {
        $this->assertSame("'stringable fixture'", $this->dumper->export(new VarDumperStringable()));
    }

    /**
     * An unserializable iterable object exports as an array of its items.
     *
     * @return void
     */
    public function testExportUnserializableIterableObject(): void
    {
        $this->assertSame(
            "[\n    'first' => 'one',\n    'second' => 'two',\n]",
            $this->dumper->export(new VarDumperIterable())
        );
    }

    /**
     * An unserializable arrayable object exports as its array.
     *
     * @return void
     */
    public function testExportUnserializableArrayableObject(): void
    {
        $this->assertSame(
            "[\n    'id' => 3,\n    'label' => 'arrayable',\n]",
            $this->dumper->export(new VarDumperArrayable())
        );
    }

    /**
     * An unserializable object with neither a string form nor items exports as the quoted string of its own dump,
     * numbered from one each time, and the export around it carries on unchanged.
     *
     * @return void
     */
    public function testExportNestedUnserializableObjectAsItsDump(): void
    {
        $value = ['first' => new VarDumperUnserializable(), 'second' => [new VarDumperUnserializable()]];
        $expected = <<<'RESULT'
[
    'first' => 'Hryvinskyi\\Base\\Test\\Unit\\Model\\Debug\\Fixture\\VarDumperUnserializable#1
(
    [name] => \'value\'
)',
    'second' => [
        'Hryvinskyi\\Base\\Test\\Unit\\Model\\Debug\\Fixture\\VarDumperUnserializable#1
(
    [name] => \'value\'
)',
    ],
]
RESULT;

        $this->assertSame($expected, $this->dumper->export($value));
    }

    /**
     * A closure with no source file exports as a placeholder closure.
     *
     * @return void
     */
    public function testExportClosureWithoutSourceFile(): void
    {
        $this->assertSame(
            'function() {/* Error: unable to determine Closure source */}',
            $this->dumper->export(\Closure::fromCallable('strlen'))
        );
    }

    /**
     * A closure whose source file cannot be read exports as a placeholder closure.
     *
     * @return void
     */
    public function testExportClosureWithUnreadableSourceFile(): void
    {
        $file = $this->createMock(File::class);
        $file->method('fileGetContents')
            ->willThrowException(new FileSystemException(new Phrase('The file cannot be read.')));

        $this->assertSame(
            'function() {/* Error: unable to determine Closure source */}',
            $this->createDumper($file)->export(function () {
                return 1;
            })
        );
    }

    /**
     * The dump of an object with __debugInfo() shows what __debugInfo() returns.
     *
     * @return void
     */
    #[Depends('testDumpObject')]
    public function testDumpClassWithCustomDebugInfo(): void
    {
        $object = new CustomDebugInfo();
        $object->volume = 10;
        $object->unitPrice = 15;
        $dumpResult = $this->dumper->dumpAsString($object);
        $this->assertStringContainsString('totalPrice', $dumpResult);
        $this->assertStringNotContainsString('unitPrice', $dumpResult);
    }

    /**
     * A __debugInfo() that returns no array is reported.
     *
     * @return void
     */
    public function testDumpRejectsDebugInfoThatIsNotAnArray(): void
    {
        $object = $this->createMock(InvalidDebugInfo::class);
        $object->method('__debugInfo')->willReturn('not an array');

        $this->expectException(InvalidValueException::class);

        $this->dumper->dumpAsString($object);
    }

    /**
     * Builds the dumper on the given file driver, with a reflection factory that builds the real reflection.
     *
     * @param DriverInterface $driver the driver that reads closure sources
     * @return VarDumper
     */
    private function createDumper(DriverInterface $driver): VarDumper
    {
        $reflectionFunctionFactory = $this->createMock(\ReflectionFunctionFactory::class);
        $reflectionFunctionFactory->method('create')->willReturnCallback(
            static function (array $data): \ReflectionFunction {
                self::assertInstanceOf(\Closure::class, $data['function']);

                return new \ReflectionFunction($data['function']);
            }
        );

        return new VarDumper(
            new Serialize(),
            new ClosureSourceReader($reflectionFunctionFactory, $driver),
            new PhpTokenHighlighter(new ZendEscaper())
        );
    }

    /**
     * Removes the highlighter's own tags, leaving the escaped text.
     *
     * @param string $highlighted the highlighted dump
     * @return string the text between the tags
     */
    private function stripHighlightMarkup(string $highlighted): string
    {
        return (string) preg_replace(self::HIGHLIGHT_MARKUP, '', $highlighted);
    }
}
