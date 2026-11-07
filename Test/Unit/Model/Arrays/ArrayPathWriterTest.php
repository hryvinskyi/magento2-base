<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayPathWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayPathWriterTest extends TestCase
{
    /**
     * @var ArrayPathWriter the service under test, built with its real collaborators
     */
    private ArrayPathWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new ArrayPathWriter();
    }

    public function testRemove(): void
    {
        $array = ['name' => 'b', 'age' => 3];
        $this->assertSame(['age' => 3], $this->writer->remove($array, 'name'));
        $this->assertSame(['name' => 'b', 'age' => 3], $array, 'the array given is left unchanged');
        $this->assertSame(['name' => 'b', 'age' => 3], $this->writer->remove($array, 'nonExisting'));
    }

    public function testRemoveTakesTheKeyLiterally(): void
    {
        $array = ['a' => ['b' => 1], 'a.b' => 2];

        $this->assertSame(['a' => ['b' => 1]], $this->writer->remove($array, 'a.b'));
        $this->assertSame(['a' => ['b' => 1]], $this->writer->remove(['a' => ['b' => 1]], 'a.b'));
    }

    public function testRemoveValueMultiple(): void
    {
        $array = [
            'Bob' => 'Dylan',
            'Michael' => 'Jackson',
            'Mick' => 'Jagger',
            'Janet' => 'Jackson',
        ];
        $removed = $this->writer->valuesIdenticalTo($array, 'Jackson');
        $this->assertEquals([
            'Bob' => 'Dylan',
            'Mick' => 'Jagger',
        ], $this->writer->removeValue($array, 'Jackson'));
        $this->assertEquals([
            'Michael' => 'Jackson',
            'Janet' => 'Jackson',
        ], $removed);
    }

    public function testRemoveValueNotExisting(): void
    {
        $array = [
            'Bob' => 'Dylan',
            'Michael' => 'Jackson',
            'Mick' => 'Jagger',
            'Janet' => 'Jackson',
        ];
        $removed = $this->writer->valuesIdenticalTo($array, 'Marley');
        $this->assertEquals([
            'Bob' => 'Dylan',
            'Michael' => 'Jackson',
            'Mick' => 'Jagger',
            'Janet' => 'Jackson',
        ], $this->writer->removeValue($array, 'Marley'));
        $this->assertEquals([], $removed);
    }

    public function testRemoveValueComparesStrictly(): void
    {
        $this->assertSame([0 => '1', 2 => true], $this->writer->removeValue(['1', 1, true], 1));
        $this->assertSame([1 => 1], $this->writer->valuesIdenticalTo(['1', 1, true], 1));
    }

    /**
     * Arrays, paths and values for testSetValue(), with the array expected after the write.
     *
     * @return list<array{0:array<array-key,mixed>,1:string|list<string>,2:mixed,3:array<array-key,mixed>}>
     */
    public static function dataProviderSetValue(): array
    {
        return [
            [
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                ],
                'key', 'val',
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key' => 'val',
                ],
            ],
            [
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                ],
                'key2', 'val',
                [
                    'key1' => 'val1',
                    'key2' => 'val',
                ],
            ],
            [
                [
                    'key1' => 'val1',
                ],
                'key.in', 'val',
                [
                    'key1' => 'val1',
                    'key' => ['in' => 'val'],
                ],
            ],
            [
                [
                    'key' => 'val1',
                ],
                'key.in', 'val',
                [
                    'key' => [
                        'val1',
                        'in' => 'val',
                    ],
                ],
            ],
            [
                [
                    'key' => 'val1',
                ],
                'key', ['in' => 'val'],
                [
                    'key' => ['in' => 'val'],
                ],
            ],
            [
                [
                    'key1' => 'val1',
                ],
                'key.in.0', 'val',
                [
                    'key1' => 'val1',
                    'key' => [
                        'in' => ['val'],
                    ],
                ],
            ],
            [
                [
                    'key1' => 'val1',
                ],
                'key.in.arr', 'val',
                [
                    'key1' => 'val1',
                    'key' => [
                        'in' => [
                            'arr' => 'val',
                        ],
                    ],
                ],
            ],
            [
                [
                    'key1' => 'val1',
                ],
                'key.in.arr', ['val'],
                [
                    'key1' => 'val1',
                    'key' => [
                        'in' => [
                            'arr' => ['val'],
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => [
                        'in' => ['val1'],
                    ],
                ],
                'key.in.arr', 'val',
                [
                    'key' => [
                        'in' => [
                            'val1',
                            'arr' => 'val',
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => ['in' => 'val1'],
                ],
                'key.in.arr', ['val'],
                [
                    'key' => [
                        'in' => [
                            'val1',
                            'arr' => ['val'],
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => [
                        'in' => [
                            'val1',
                            'key' => 'val',
                        ],
                    ],
                ],
                'key.in.0', ['arr' => 'val'],
                [
                    'key' => [
                        'in' => [
                            ['arr' => 'val'],
                            'key' => 'val',
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => [
                        'in' => [
                            'val1',
                            'key' => 'val',
                        ],
                    ],
                ],
                'key.in', ['arr' => 'val'],
                [
                    'key' => [
                        'in' => ['arr' => 'val'],
                    ],
                ],
            ],
            [
                [
                    'key' => [
                        'in' => [
                            'key' => 'val',
                            'data' => [
                                'attr1',
                                'attr2',
                                'attr3',
                            ],
                        ],
                    ],
                ],
                'key.in.schema', 'array',
                [
                    'key' => [
                        'in' => [
                            'key' => 'val',
                            'schema' => 'array',
                            'data' => [
                                'attr1',
                                'attr2',
                                'attr3',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => [
                        'in.array' => [
                            'key' => 'val',
                        ],
                    ],
                ],
                ['key', 'in.array', 'ok.schema'], 'array',
                [
                    'key' => [
                        'in.array' => [
                            'key' => 'val',
                            'ok.schema' => 'array',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<array-key,mixed> $arrayInput the array written to
     * @param string|list<string> $key the path written
     * @param mixed $value the value written
     * @param array<array-key,mixed> $expected the array expected after the write
     */
    #[DataProvider('dataProviderSetValue')]
    public function testSetValue(array $arrayInput, string|array $key, mixed $value, array $expected): void
    {
        $original = $arrayInput;
        $this->assertEquals($expected, $this->writer->setValue($arrayInput, $key, $value));
        $this->assertSame($original, $arrayInput, 'the array given is left unchanged');
    }

    public function testSetValueWrapsANullOnThePathIntoAnEmptyArray(): void
    {
        $this->assertSame(['key' => ['in' => 'val']], $this->writer->setValue(['key' => null], 'key.in', 'val'));
    }

    public function testSetValueTakesAnIntegerPathAsAKey(): void
    {
        $this->assertSame([5 => 'val'], $this->writer->setValue([], 5, 'val'));
    }
}
