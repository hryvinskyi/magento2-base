<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers;

use Hryvinskyi\Base\Helper\VarDumper;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\CustomDebugInfo;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\VarDumperIterable;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\VarDumperStringable;
use Magento\Framework\Serialize\Serializer\Serialize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class VarDumperTest extends TestCase
{
    /**
     * An object of a class that does not exist dumps as an incomplete class naming the missing class.
     *
     * @return void
     */
    public function testDumpIncompleteObject(): void
    {
        $incompleteObj = (new Serialize())->unserialize('O:16:"nonExistingClass":0:{}');
        $dumpResult = VarDumper::dumpAsString($incompleteObj);
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
        $exportResult = VarDumper::export($incompleteObj);
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
        $this->assertEquals("stdClass#1\n(\n)", VarDumper::dumpAsString($obj));
        $obj = new \stdClass();
        $obj->name = 'test-name';
        $obj->price = 19;
        $dumpResult = VarDumper::dumpAsString($obj);
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
        $this->assertSame($expected, VarDumper::dumpAsString($var, $depth));
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
        $this->assertSame('{resource}', VarDumper::dumpAsString($resource));
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
            VarDumper::dumpAsString([$obj, $obj])
        );
    }

    /**
     * The highlighted dump is HTML without the PHP open tag used for highlighting.
     *
     * @return void
     */
    public function testDumpHighlighted(): void
    {
        $dumpResult = VarDumper::dumpAsString(75, 10, true);
        $this->assertStringContainsString('75', $dumpResult);
        $this->assertStringContainsString('<code', $dumpResult);
        $this->assertStringNotContainsString('&lt;?php', $dumpResult);
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
        $exportResult = VarDumper::export($var);
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
        $exportResult = VarDumper::export($var);
        $this->assertNotEmpty($exportResult);
        $master = new \stdClass();
        $slave = new \stdClass();
        $master->slave = $slave;
        $slave->master = $master;
        $master->function = function () {
            return true;
        };
        $exportResult = VarDumper::export($master);
        $this->assertNotEmpty($exportResult);
    }

    /**
     * An unserializable object with __toString() exports as its string.
     *
     * @return void
     */
    public function testExportUnserializableStringableObject(): void
    {
        $this->assertSame("'stringable fixture'", VarDumper::export(new VarDumperStringable()));
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
            VarDumper::export(new VarDumperIterable())
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
        $dumpResult = VarDumper::dumpAsString($object);
        $this->assertStringContainsString('totalPrice', $dumpResult);
        $this->assertStringNotContainsString('unitPrice', $dumpResult);
    }
}
