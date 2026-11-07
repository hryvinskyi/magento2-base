<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Test\Unit\Helpers;

use Hryvinskyi\Base\Helper\Arrayable;
use Hryvinskyi\Base\Helper\ArrayHelper;
use Hryvinskyi\Base\Helper\ReplaceArrayValue;
use Hryvinskyi\Base\Helper\UnsetArrayValue;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\ArrayHelperCollection;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\ArrayHelperPost1;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\ArrayHelperPost2;
use Hryvinskyi\Base\Test\Unit\Helpers\Fixture\ArrayHelperPost3;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testToArray(): void
    {
        $dataArrayable = $this->createMock(Arrayable::class);
        $dataArrayable->method('toArray')->willReturn([]);
        $this->assertEquals([], ArrayHelper::toArray($dataArrayable));
        $this->assertEquals(['foo'], ArrayHelper::toArray('foo'));
        $object = new ArrayHelperPost1();
        $this->assertEquals(get_object_vars($object), ArrayHelper::toArray($object));
        $object = new ArrayHelperPost2();
        $this->assertEquals(get_object_vars($object), ArrayHelper::toArray($object));
        $object1 = new ArrayHelperPost1();
        $object2 = new ArrayHelperPost2();
        $this->assertEquals([
            get_object_vars($object1),
            get_object_vars($object2),
        ], ArrayHelper::toArray([
            $object1,
            $object2,
        ]));
        $object = new ArrayHelperPost2();
        $this->assertEquals([
            'id' => 123,
            'secret' => 's',
            '_content' => 'test',
            'length' => 4,
        ], ArrayHelper::toArray($object, [
            ArrayHelperPost2::class => [
                'id',
                'secret',
                '_content' => 'content',
                'length' => static fn (ArrayHelperPost2 $post): int => strlen($post->content),
            ],
        ]));
        $object = new ArrayHelperPost3();
        $this->assertEquals(get_object_vars($object), ArrayHelper::toArray($object, [], false));
        $this->assertEquals(
            [
                'id' => 33,
                'subObject' => [
                    'id' => 123,
                    'content' => 'test',
                ],
            ],
            ArrayHelper::toArray($object)
        );
        //recursive with attributes of object and subobject
        $this->assertEquals([
            'id' => 33,
            'id_plus_1' => 34,
            'subObject' => [
                'id' => 123,
                'id_plus_1' => 124,
            ],
        ], ArrayHelper::toArray($object, [
            ArrayHelperPost3::class => [
                'id', 'subObject',
                'id_plus_1' => static fn (ArrayHelperPost3 $post): int => $post->id + 1,
            ],
            ArrayHelperPost2::class => [
                'id',
                'id_plus_1' => static fn (ArrayHelperPost2 $post): int => $post->id + 1,
            ],
        ]));
        //recursive with attributes of subobject only
        $this->assertEquals([
            'id' => 33,
            'subObject' => [
                'id' => 123,
                'id_plus_1' => 124,
            ],
        ], ArrayHelper::toArray($object, [
            ArrayHelperPost2::class => [
                'id',
                'id_plus_1' => static fn (ArrayHelperPost2 $post): int => $post->id + 1,
            ],
        ]));
    }

    public function testRemove(): void
    {
        $array = ['name' => 'b', 'age' => 3];
        $name = ArrayHelper::remove($array, 'name');
        $this->assertEquals($name, 'b');
        $this->assertEquals($array, ['age' => 3]);
        $default = ArrayHelper::remove($array, 'nonExisting', 'defaultValue');
        $this->assertEquals('defaultValue', $default);
    }
    public function testRemoveValueMultiple(): void
    {
        $array = [
            'Bob' => 'Dylan',
            'Michael' => 'Jackson',
            'Mick' => 'Jagger',
            'Janet' => 'Jackson',
        ];
        $removed = ArrayHelper::removeValue($array, 'Jackson');
        $this->assertEquals([
            'Bob' => 'Dylan',
            'Mick' => 'Jagger',
        ], $array);
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
        $removed = ArrayHelper::removeValue($array, 'Marley');
        $this->assertEquals([
            'Bob' => 'Dylan',
            'Michael' => 'Jackson',
            'Mick' => 'Jagger',
            'Janet' => 'Jackson',
        ], $array);
        $this->assertEquals([], $removed);
    }
    public function testMultisort(): void
    {
        // empty key
        $dataEmpty = [];
        ArrayHelper::multisort($dataEmpty, '');
        $this->assertEquals([], $dataEmpty);
        // single key
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 1],
            ['name' => 'c', 'age' => 2],
        ];
        ArrayHelper::multisort($array, 'name');
        $this->assertEquals(['name' => 'a', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $array[1]);
        $this->assertEquals(['name' => 'c', 'age' => 2], $array[2]);
        // multiple keys
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 2],
            ['name' => 'a', 'age' => 1],
        ];
        ArrayHelper::multisort($array, ['name', 'age']);
        $this->assertEquals(['name' => 'a', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'a', 'age' => 2], $array[1]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $array[2]);
        // case-insensitive
        $array = [
            ['name' => 'a', 'age' => 3],
            ['name' => 'b', 'age' => 2],
            ['name' => 'B', 'age' => 4],
            ['name' => 'A', 'age' => 1],
        ];
        ArrayHelper::multisort($array, ['name', 'age'], SORT_ASC, [SORT_STRING, SORT_REGULAR]);
        $this->assertEquals(['name' => 'A', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'B', 'age' => 4], $array[1]);
        $this->assertEquals(['name' => 'a', 'age' => 3], $array[2]);
        $this->assertEquals(['name' => 'b', 'age' => 2], $array[3]);
        ArrayHelper::multisort($array, ['name', 'age'], SORT_ASC, [SORT_STRING | SORT_FLAG_CASE, SORT_REGULAR]);
        $this->assertEquals(['name' => 'A', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'a', 'age' => 3], $array[1]);
        $this->assertEquals(['name' => 'b', 'age' => 2], $array[2]);
        $this->assertEquals(['name' => 'B', 'age' => 4], $array[3]);
    }
    public function testMultisortNestedObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->type = 'def';
        $obj1->owner = $obj1;
        $obj2 = new \stdClass();
        $obj2->type = 'abc';
        $obj2->owner = $obj2;
        $obj3 = new \stdClass();
        $obj3->type = 'abc';
        $obj3->owner = $obj3;
        $models = [
            $obj1,
            $obj2,
            $obj3,
        ];
        $this->assertEquals($obj2, $obj3);
        ArrayHelper::multisort($models, 'type', SORT_ASC);
        $this->assertEquals($obj2, $models[0]);
        $this->assertEquals($obj3, $models[1]);
        $this->assertEquals($obj1, $models[2]);
        ArrayHelper::multisort($models, 'type', SORT_DESC);
        $this->assertEquals($obj1, $models[0]);
        $this->assertEquals($obj2, $models[1]);
        $this->assertEquals($obj3, $models[2]);
    }
    public function testMultisortUseSort(): void
    {
        // single key
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 1],
            ['name' => 'c', 'age' => 2],
        ];
        ArrayHelper::multisort($array, ['name'], [SORT_ASC]);
        $this->assertEquals(['name' => 'a', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $array[1]);
        $this->assertEquals(['name' => 'c', 'age' => 2], $array[2]);

        // multiple keys
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 2],
            ['name' => 'a', 'age' => 1],
        ];
        ArrayHelper::multisort($array, ['name', 'age'], [SORT_ASC, SORT_DESC]);
        $this->assertEquals(['name' => 'a', 'age' => 2], $array[0]);
        $this->assertEquals(['name' => 'a', 'age' => 1], $array[1]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $array[2]);
    }

    public function testMultisortClosure(): void
    {
        $changelog = [
            '- Enh #123: test1',
            '- Bug #125: test2',
            '- Bug #123: test2',
            '- Enh: test3',
            '- Bug: test4',
        ];
        $i = 0;
        ArrayHelper::multisort($changelog, function (string $line) use (&$i): string {
            if (preg_match('/^- (Enh|Bug)( #\d+)?: .+$/', $line, $m)) {
                $o = ['Bug' => 'C', 'Enh' => 'D'];
                return $o[$m[1]] . ' ' . (!empty($m[2]) ? $m[2] : 'AAAA' . ($i++));
            }
            return 'B' . ($i++);
        }, SORT_ASC, SORT_NATURAL);
        $this->assertEquals([
            '- Bug #123: test2',
            '- Bug #125: test2',
            '- Bug: test4',
            '- Enh #123: test1',
            '- Enh: test3',
        ], $changelog);
    }

    public function testMultisortInvalidParamExceptionDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The length of $direction parameter must be the same as that of $keys.');
        $data = ['foo' => 'bar'];
        ArrayHelper::multisort($data, ['foo'], []);
    }

    public function testMultisortInvalidParamExceptionSortFlag(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The length of $sortFlag parameter must be the same as that of $keys.');
        $data = ['foo' => 'bar'];
        ArrayHelper::multisort($data, ['foo'], [SORT_ASC], []);
    }
    public function testMerge(): void
    {
        $a = [
            'name' => 'Yii',
            'version' => '1.0',
            'options' => [
                'namespace' => false,
                'unittest' => false,
            ],
            'features' => [
                'mvc',
            ],
        ];
        $b = [
            'version' => '1.1',
            'options' => [
                'unittest' => true,
            ],
            'features' => [
                'gii',
            ],
        ];
        $c = [
            'version' => '2.0',
            'options' => [
                'namespace' => true,
            ],
            'features' => [
                'debug',
            ],
            'foo',
        ];
        $result = ArrayHelper::merge($a, $b, $c);
        $expected = [
            'name' => 'Yii',
            'version' => '2.0',
            'options' => [
                'namespace' => true,
                'unittest' => true,
            ],
            'features' => [
                'mvc',
                'gii',
                'debug',
            ],
            'foo',
        ];
        $this->assertEquals($expected, $result);
    }
    public function testMergeTakesATraversableEntryByEntry(): void
    {
        $merged = ArrayHelper::merge(
            ['name' => 'a', 'tags' => ['x']],
            new ArrayHelperCollection(['name' => 'b', 'tags' => ['y']])
        );

        $this->assertSame(['name' => 'b', 'tags' => ['x', 'y']], $merged);
    }

    public function testMergeTakesAnObjectByItsPublicProperties(): void
    {
        $object = new \stdClass();
        $object->name = 'b';

        $this->assertSame(['name' => 'b', 0 => 'x'], ArrayHelper::merge(['name' => 'a'], ['x'], $object));
    }

    public function testMergeSkipsAScalarArgument(): void
    {
        $this->assertSame(['name' => 'a'], ArrayHelper::merge(['name' => 'a'], ['name' => 'a'], 'text'));
    }

    public function testMergeWithUnset(): void
    {
        $a = [
            'name' => 'Yii',
            'version' => '1.0',
            'options' => [
                'namespace' => false,
                'unittest' => false,
            ],
            'features' => [
                'mvc',
            ],
        ];
        $b = [
            'version' => '1.1',
            'options' => new UnsetArrayValue(),
            'features' => [
                'gii',
            ],
        ];
        $result = ArrayHelper::merge($a, $b);
        $expected = [
            'name' => 'Yii',
            'version' => '1.1',
            'features' => [
                'mvc',
                'gii',
            ],
        ];
        $this->assertEquals($expected, $result);
    }
    public function testMergeWithReplace(): void
    {
        $a = [
            'name' => 'Yii',
            'version' => '1.0',
            'options' => [
                'namespace' => false,
                'unittest' => false,
            ],
            'features' => [
                'mvc',
            ],
        ];
        $b = [
            'version' => '1.1',
            'options' => [
                'unittest' => true,
            ],
            'features' => new ReplaceArrayValue([
                'gii',
            ]),
        ];
        $result = ArrayHelper::merge($a, $b);
        $expected = [
            'name' => 'Yii',
            'version' => '1.1',
            'options' => [
                'namespace' => false,
                'unittest' => true,
            ],
            'features' => [
                'gii',
            ],
        ];
        $this->assertEquals($expected, $result);
    }
    public function testMergeWithNullValues(): void
    {
        $a = [
            'firstValue',
            null,
        ];
        $b = [
            'secondValue',
            'thirdValue',
        ];
        $result = ArrayHelper::merge($a, $b);
        $expected = [
            'firstValue',
            null,
            'secondValue',
            'thirdValue',
        ];
        $this->assertEquals($expected, $result);
    }
    public function testMergeEmpty(): void
    {
        $this->assertEquals([], ArrayHelper::merge([], []));
        $this->assertEquals([], ArrayHelper::merge([], [], []));
    }
    /**
     * A float key finds the integer key PHP stores it under.
     *
     * @see https://github.com/yiisoft/yii2/pull/11549
     */
    public function testGetValueWithFloatKey(): void
    {
        $array = [1 => 'some value'];
        $result = ArrayHelper::getValue($array, 1.0);
        $this->assertEquals('some value', $result);
    }
    public function testIndex(): void
    {
        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '345', 'data' => 'def'],
            ['id' => '345', 'data' => 'ghi'],
        ];
        $result = ArrayHelper::index($array, 'id');
        $this->assertEquals([
            '123' => ['id' => '123', 'data' => 'abc'],
            '345' => ['id' => '345', 'data' => 'ghi'],
        ], $result);
        $result = ArrayHelper::index($array, self::dataOf(...));
        $this->assertEquals([
            'abc' => ['id' => '123', 'data' => 'abc'],
            'def' => ['id' => '345', 'data' => 'def'],
            'ghi' => ['id' => '345', 'data' => 'ghi'],
        ], $result);
        $result = ArrayHelper::index($array, null);
        $this->assertEquals([], $result);
        $result = ArrayHelper::index($array, static fn (): mixed => null);
        $this->assertEquals([], $result);
        $result = ArrayHelper::index(
            $array,
            static fn (array $element): ?string => self::idOf($element) === '345' ? null : self::idOf($element)
        );
        $this->assertEquals([
            '123' => ['id' => '123', 'data' => 'abc'],
        ], $result);
    }
    public function testIndexGroupBy(): void
    {
        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '345', 'data' => 'def'],
            ['id' => '345', 'data' => 'ghi'],
        ];
        $expected = [
            '123' => [
                ['id' => '123', 'data' => 'abc'],
            ],
            '345' => [
                ['id' => '345', 'data' => 'def'],
                ['id' => '345', 'data' => 'ghi'],
            ],
        ];
        $result = ArrayHelper::index($array, null, ['id']);
        $this->assertEquals($expected, $result);
        $result = ArrayHelper::index($array, null, 'id');
        $this->assertEquals($expected, $result);
        $result = ArrayHelper::index($array, null, ['id', 'data']);
        $this->assertEquals([
            '123' => [
                'abc' => [
                    ['id' => '123', 'data' => 'abc'],
                ],
            ],
            '345' => [
                'def' => [
                    ['id' => '345', 'data' => 'def'],
                ],
                'ghi' => [
                    ['id' => '345', 'data' => 'ghi'],
                ],
            ],
        ], $result);
        $expected = [
            '123' => [
                'abc' => ['id' => '123', 'data' => 'abc'],
            ],
            '345' => [
                'def' => ['id' => '345', 'data' => 'def'],
                'ghi' => ['id' => '345', 'data' => 'ghi'],
            ],
        ];
        $result = ArrayHelper::index($array, 'data', ['id']);
        $this->assertEquals($expected, $result);
        $result = ArrayHelper::index($array, 'data', 'id');
        $this->assertEquals($expected, $result);
        $result = ArrayHelper::index($array, self::dataOf(...), 'id');
        $this->assertEquals($expected, $result);
        $expected = [
            '123' => [
                'abc' => [
                    'abc' => ['id' => '123', 'data' => 'abc'],
                ],
            ],
            '345' => [
                'def' => [
                    'def' => ['id' => '345', 'data' => 'def'],
                ],
                'ghi' => [
                    'ghi' => ['id' => '345', 'data' => 'ghi'],
                ],
            ],
        ];
        $result = ArrayHelper::index($array, 'data', ['id', 'data']);
        $this->assertEquals($expected, $result);
        $result = ArrayHelper::index($array, self::dataOf(...), ['id', 'data']);
        $this->assertEquals($expected, $result);
    }
    /**
     * @see https://github.com/yiisoft/yii2/issues/11739
     */
    public function testIndexFloat(): void
    {
        $array = [
            ['id' => 1e6],
            ['id' => 1e32],
            ['id' => 1e64],
            ['id' => 1465540807.522109],
        ];
        $expected = [
            '1000000' => ['id' => 1e6],
            '1.0E+32' => ['id' => 1e32],
            '1.0E+64' => ['id' => 1e64],
            '1465540807.5221' => ['id' => 1465540807.522109],
        ];
        $result = ArrayHelper::index($array, 'id');
        $this->assertEquals($expected, $result);
    }
    public function testGetColumn(): void
    {
        $array = [
            'a' => ['id' => '123', 'data' => 'abc'],
            'b' => ['id' => '345', 'data' => 'def'],
        ];
        $result = ArrayHelper::getColumn($array, 'id');
        $this->assertEquals(['a' => '123', 'b' => '345'], $result);
        $result = ArrayHelper::getColumn($array, 'id', false);
        $this->assertEquals(['123', '345'], $result);
        $result = ArrayHelper::getColumn($array, self::dataOf(...));
        $this->assertEquals(['a' => 'abc', 'b' => 'def'], $result);
        $result = ArrayHelper::getColumn($array, self::dataOf(...), false);
        $this->assertEquals(['abc', 'def'], $result);
    }
    public function testMap(): void
    {
        $array = [
            ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
            ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
            ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
        ];
        $result = ArrayHelper::map($array, 'id', 'name');
        $this->assertEquals([
            '123' => 'aaa',
            '124' => 'bbb',
            '345' => 'ccc',
        ], $result);
        $result = ArrayHelper::map($array, 'id', 'name', 'class');
        $this->assertEquals([
            'x' => [
                '123' => 'aaa',
                '124' => 'bbb',
            ],
            'y' => [
                '345' => 'ccc',
            ],
        ], $result);
    }
    public function testKeyExists(): void
    {
        $array = [
            'a' => 1,
            'B' => 2,
        ];
        $this->assertTrue(ArrayHelper::keyExists('a', $array));
        $this->assertFalse(ArrayHelper::keyExists('b', $array));
        $this->assertTrue(ArrayHelper::keyExists('B', $array));
        $this->assertFalse(ArrayHelper::keyExists('c', $array));
        $this->assertTrue(ArrayHelper::keyExists('a', $array, false));
        $this->assertTrue(ArrayHelper::keyExists('b', $array, false));
        $this->assertTrue(ArrayHelper::keyExists('B', $array, false));
        $this->assertFalse(ArrayHelper::keyExists('c', $array, false));
    }
    /**
     * Keys to look up in the array of testGetValue(), with the value expected and the default given.
     *
     * @return list<array{0:string|\Closure|list<string>,1:string|int|null,2?:string}>
     */
    public static function valueProvider(): array
    {
        return [
            ['name', 'test'],
            ['noname', null],
            ['noname', 'test', 'test'],
            ['post.id', 5],
            ['post.id', 5, 'test'],
            ['nopost.id', null],
            ['nopost.id', 'test', 'test'],
            ['post.author.name', 'cebe'],
            ['post.author.noname', null],
            ['post.author.noname', 'test', 'test'],
            ['post.author.profile.title', '1337'],
            ['admin.firstname', 'Qiang'],
            ['admin.firstname', 'Qiang', 'test'],
            ['admin.lastname', 'Xue'],
            [
                static fn (array $array, string $defaultValue): string => self::dateOf($array) . $defaultValue,
                '31-12-2113test',
                'test',
            ],
            [['version', '1.0', 'status'], 'released'],
            [['version', '1.0', 'date'], 'defaultValue', 'defaultValue'],
        ];
    }
    /**
     * @param string|\Closure|list<string> $key the key to look up
     * @param string|int|null $expected the value expected
     * @param string|null $default the default value given
     */
    #[DataProvider('valueProvider')]
    public function testGetValue(string|\Closure|array $key, string|int|null $expected, ?string $default = null): void
    {
        $array = [
            'name' => 'test',
            'date' => '31-12-2113',
            'post' => [
                'id' => 5,
                'author' => [
                    'name' => 'cebe',
                    'profile' => [
                        'title' => '1337',
                    ],
                ],
            ],
            'admin.firstname' => 'Qiang',
            'admin.lastname' => 'Xue',
            'admin' => [
                'lastname' => 'cebe',
            ],
            'version' => [
                '1.0' => [
                    'status' => 'released',
                ],
            ],
        ];
        $this->assertEquals($expected, ArrayHelper::getValue($array, $key, $default));
    }
    public function testGetValueObjects(): void
    {
        $collection = new ArrayHelperCollection(['id' => 23]);
        $this->assertEquals(23, ArrayHelper::getValue($collection, 'id'));
        $object = new ArrayHelperPost1();
        $this->assertEquals(23, ArrayHelper::getValue($object, 'id'));
    }
    public function testGetValueNonexistingProperties1(): void
    {
        $object = new ArrayHelperPost1();
        [$value, $warnings] = $this->captureWarnings(
            static fn () => ArrayHelper::getValue($object, 'nonExisting')
        );
        $this->assertNull($value);
        $this->assertSame(['Undefined property: ' . ArrayHelperPost1::class . '::$nonExisting'], $warnings);
    }

    public function testGetValueNonexistingProperties2(): void
    {
        $collection = new ArrayHelperCollection(['id' => 23]);
        [$value, $warnings] = $this->captureWarnings(
            static fn () => ArrayHelper::getValue($collection, 'nonExisting')
        );
        $this->assertNull($value);
        $this->assertSame(['Undefined array key "nonExisting"'], $warnings);
    }

    /**
     * Runs the callback and collects the PHP warnings it raises, so a test can assert them.
     *
     * @param callable(): mixed $callback
     * @return array{0:mixed,1:list<string>}
     */
    private function captureWarnings(callable $callback): array
    {
        $warnings = [];
        set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        }, E_WARNING);
        try {
            $value = $callback();
        } finally {
            restore_error_handler();
        }

        return [$value, $warnings];
    }
    /**
     * Arrays, paths and values for testSetValue(), with the array expected after the write.
     *
     * @return list<array{0:array<array-key,mixed>,1:string|list<string>|null,2:mixed,3:mixed}>
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
            [
                [
                    'key' => ['val'],
                ],
                null, 'data',
                'data',
            ],
        ];
    }
    /**
     * @param array<array-key,mixed> $arrayInput the array written to
     * @param string|list<string>|null $key the path written
     * @param mixed $value the value written
     * @param mixed $expected the array expected after the write
     */
    #[DataProvider('dataProviderSetValue')]
    public function testSetValue(array $arrayInput, string|array|null $key, mixed $value, mixed $expected): void
    {
        ArrayHelper::setValue($arrayInput, $key, $value);
        $this->assertEquals($expected, $arrayInput);
    }
    public function testIsAssociative(): void
    {
        $this->assertFalse(ArrayHelper::isAssociative('test'));
        $this->assertFalse(ArrayHelper::isAssociative([]));
        $this->assertFalse(ArrayHelper::isAssociative([1, 2, 3]));
        $this->assertFalse(ArrayHelper::isAssociative([1], false));
        $this->assertTrue(ArrayHelper::isAssociative(['name' => 1, 'value' => 'test']));
        $this->assertFalse(ArrayHelper::isAssociative(['name' => 1, 'value' => 'test', 3]));
        $this->assertTrue(ArrayHelper::isAssociative(['name' => 1, 'value' => 'test', 3], false));
    }
    public function testIsIndexed(): void
    {
        $this->assertFalse(ArrayHelper::isIndexed('test'));
        $this->assertTrue(ArrayHelper::isIndexed([]));
        $this->assertTrue(ArrayHelper::isIndexed([1, 2, 3]));
        $this->assertTrue(ArrayHelper::isIndexed([2 => 'a', 3 => 'b']));
        $this->assertFalse(ArrayHelper::isIndexed([2 => 'a', 3 => 'b'], true));
        $this->assertFalse(ArrayHelper::isIndexed(['a' => 'b'], false));
    }
    public function testHtmlEncode(): void
    {
        $array = [
            'abc' => '123',
            '<' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
            'invalid' => "a\x80b",
        ];
        $this->assertEquals([
            'abc' => '123',
            '<' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a&lt;&gt;b',
                '23' => true,
            ],
            'invalid' => 'a�b',
        ], ArrayHelper::htmlEncode($array));
        $this->assertEquals([
            'abc' => '123',
            '&lt;' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '&lt;&gt;' => 'a&lt;&gt;b',
                '23' => true,
            ],
            'invalid' => 'a�b',
        ], ArrayHelper::htmlEncode($array, false));
    }
    public function testHtmlDecode(): void
    {
        $array = [
            'abc' => '123',
            '&lt;' => '&gt;',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a&lt;&gt;b',
                '23' => true,
            ],
        ];
        $this->assertEquals([
            'abc' => '123',
            '&lt;' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
        ], ArrayHelper::htmlDecode($array));
        $this->assertEquals([
            'abc' => '123',
            '<' => '>',
            'cde' => false,
            3 => 'blank',
            [
                '<>' => 'a<>b',
                '23' => true,
            ],
        ], ArrayHelper::htmlDecode($array, false));
    }

    public function testIsIn(): void
    {
        $this->assertTrue(ArrayHelper::isIn('a', new ArrayHelperCollection(['a', 'b'])));
        $this->assertTrue(ArrayHelper::isIn('a', ['a', 'b']));
        $this->assertTrue(ArrayHelper::isIn('1', new ArrayHelperCollection([1, 'b'])));
        $this->assertTrue(ArrayHelper::isIn('1', [1, 'b']));
        $this->assertFalse(ArrayHelper::isIn('1', new ArrayHelperCollection([1, 'b']), true));
        $this->assertFalse(ArrayHelper::isIn('1', [1, 'b'], true));
        $this->assertTrue(ArrayHelper::isIn(['a'], new ArrayHelperCollection([['a'], 'b'])));
        $this->assertFalse(ArrayHelper::isIn('a', new ArrayHelperCollection([['a'], 'b'])));
        $this->assertFalse(ArrayHelper::isIn('a', [['a'], 'b']));
    }

    public function testIsInStrict(): void
    {
        // strict comparison
        $this->assertTrue(ArrayHelper::isIn(1, new ArrayHelperCollection([1, 'a']), true));
        $this->assertTrue(ArrayHelper::isIn(1, [1, 'a'], true));
        $this->assertFalse(ArrayHelper::isIn('1', new ArrayHelperCollection([1, 'a']), true));
        $this->assertFalse(ArrayHelper::isIn('1', [1, 'a'], true));
    }

    public function testInException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument $haystack must be an array or implement Traversable');
        ArrayHelper::isIn('value', null);
    }

    public function testIsSubset(): void
    {
        $this->assertTrue(ArrayHelper::isSubset(['a'], new ArrayHelperCollection(['a', 'b'])));
        $this->assertTrue(ArrayHelper::isSubset(new ArrayHelperCollection(['a']), ['a', 'b']));
        $this->assertTrue(ArrayHelper::isSubset([1], new ArrayHelperCollection(['1', 'b'])));
        $this->assertTrue(ArrayHelper::isSubset(new ArrayHelperCollection([1]), ['1', 'b']));
        $this->assertFalse(ArrayHelper::isSubset([1], new ArrayHelperCollection(['1', 'b']), true));
        $this->assertFalse(ArrayHelper::isSubset(new ArrayHelperCollection([1]), ['1', 'b'], true));
    }

    public function testIsSubsetException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument $needles must be an array or implement Traversable');
        ArrayHelper::isSubset('a', new ArrayHelperCollection(['a', 'b']));
    }

    /**
     * Values for testIsTraversable(), with whether each is an array or Traversable.
     *
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function traversableProvider(): array
    {
        return [
            'array' => [['a'], true],
            'traversable object' => [new ArrayHelperCollection(['1']), true],
            'plain object' => [new \stdClass(), false],
            'string' => ['A,B,C', false],
            'integer' => [12, false],
            'boolean' => [false, false],
            'null' => [null, false],
        ];
    }

    #[DataProvider('traversableProvider')]
    public function testIsTraversable(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, ArrayHelper::isTraversable($value));
    }

    public function testFilter(): void
    {
        $array = [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
            'G' => 1,
        ];
        $this->assertEquals(ArrayHelper::filter($array, ['A']), [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
        ]);
        $this->assertEquals(ArrayHelper::filter($array, ['A.B']), [
            'A' => [
                'B' => 1,
            ],
        ]);
        $this->assertEquals(ArrayHelper::filter($array, ['A', '!A.B']), [
            'A' => [
                'C' => 2,
            ],
        ]);
        $this->assertEquals(ArrayHelper::filter($array, ['!A.B', 'A']), [
            'A' => [
                'C' => 2,
            ],
        ]);
        $this->assertEquals(ArrayHelper::filter($array, ['A', 'G']), [
            'A' => [
                'B' => 1,
                'C' => 2,
            ],
            'G' => 1,
        ]);
        $this->assertEquals(ArrayHelper::filter($array, ['X']), []);
        $this->assertEquals(ArrayHelper::filter($array, ['X.Y']), []);
        $this->assertEquals(ArrayHelper::filter($array, ['A.X']), []);
    }

    /**
     * Returns the `data` column of a row of the index and column tests.
     *
     * @param array<mixed> $element the row
     * @return string the column value
     */
    private static function dataOf(array $element): string
    {
        self::assertIsString($element['data']);

        return $element['data'];
    }

    /**
     * Returns the `id` column of a row of the index tests.
     *
     * @param array<mixed> $element the row
     * @return string the column value
     */
    private static function idOf(array $element): string
    {
        self::assertIsString($element['id']);

        return $element['id'];
    }

    /**
     * Returns the `date` element of the array of testGetValue().
     *
     * @param array<mixed> $array the array
     * @return string the element value
     */
    private static function dateOf(array $array): string
    {
        self::assertIsString($array['date']);

        return $array['date'];
    }
}
