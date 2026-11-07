<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayIndexer;
use Hryvinskyi\Base\Model\Arrays\ArrayKeyNormalizer;
use Hryvinskyi\Base\Model\Arrays\ArrayPathReader;
use PHPUnit\Framework\TestCase;

class ArrayIndexerTest extends TestCase
{
    /**
     * @var ArrayIndexer the service under test, built with its real collaborators
     */
    private ArrayIndexer $indexer;

    protected function setUp(): void
    {
        $keys = new ArrayKeyNormalizer();
        $this->indexer = new ArrayIndexer(new ArrayPathReader($keys), $keys);
    }

    public function testIndex(): void
    {
        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '345', 'data' => 'def'],
            ['id' => '345', 'data' => 'ghi'],
        ];
        $result = $this->indexer->index($array, 'id');
        $this->assertEquals([
            '123' => ['id' => '123', 'data' => 'abc'],
            '345' => ['id' => '345', 'data' => 'ghi'],
        ], $result);
        $result = $this->indexer->index($array, self::dataOf(...));
        $this->assertEquals([
            'abc' => ['id' => '123', 'data' => 'abc'],
            'def' => ['id' => '345', 'data' => 'def'],
            'ghi' => ['id' => '345', 'data' => 'ghi'],
        ], $result);
        $result = $this->indexer->index($array, null);
        $this->assertEquals([], $result);
        $result = $this->indexer->index($array, static fn (): mixed => null);
        $this->assertEquals([], $result);
        $result = $this->indexer->index(
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
        $result = $this->indexer->index($array, null, ['id']);
        $this->assertEquals($expected, $result);
        $result = $this->indexer->index($array, null, 'id');
        $this->assertEquals($expected, $result);
        $result = $this->indexer->index($array, null, ['id', 'data']);
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
        $result = $this->indexer->index($array, 'data', ['id']);
        $this->assertEquals($expected, $result);
        $result = $this->indexer->index($array, 'data', 'id');
        $this->assertEquals($expected, $result);
        $result = $this->indexer->index($array, self::dataOf(...), 'id');
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
        $result = $this->indexer->index($array, 'data', ['id', 'data']);
        $this->assertEquals($expected, $result);
        $result = $this->indexer->index($array, self::dataOf(...), ['id', 'data']);
        $this->assertEquals($expected, $result);
    }

    public function testIndexKeepsTheGroupOfARowWhoseKeyValueIsNull(): void
    {
        $this->assertSame(
            ['123' => []],
            $this->indexer->index([['id' => '123', 'data' => null]], 'data', 'id')
        );
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
        $result = $this->indexer->index($array, 'id');
        $this->assertEquals($expected, $result);
    }

    public function testIndexRefusesAKeyValueThatCannotBeAnArrayKey(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Cannot access offset of type array on array');

        $this->indexer->index([['id' => ['nested']]], 'id');
    }

    public function testGetColumn(): void
    {
        $array = [
            'a' => ['id' => '123', 'data' => 'abc'],
            'b' => ['id' => '345', 'data' => 'def'],
        ];
        $result = $this->indexer->getColumn($array, 'id');
        $this->assertEquals(['a' => '123', 'b' => '345'], $result);
        $result = $this->indexer->getColumn($array, 'id', false);
        $this->assertEquals(['123', '345'], $result);
        $result = $this->indexer->getColumn($array, self::dataOf(...));
        $this->assertEquals(['a' => 'abc', 'b' => 'def'], $result);
        $result = $this->indexer->getColumn($array, self::dataOf(...), false);
        $this->assertEquals(['abc', 'def'], $result);
    }

    public function testMap(): void
    {
        $array = [
            ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
            ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
            ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
        ];
        $result = $this->indexer->map($array, 'id', 'name');
        $this->assertEquals([
            '123' => 'aaa',
            '124' => 'bbb',
            '345' => 'ccc',
        ], $result);
        $result = $this->indexer->map($array, 'id', 'name', 'class');
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

    public function testMapReadsDotPathsAndClosures(): void
    {
        $array = [
            ['id' => '1', 'author' => ['name' => 'a']],
            ['id' => '2', 'author' => ['name' => 'b']],
        ];

        $this->assertSame(
            [1 => 'a', 2 => 'b'],
            $this->indexer->map($array, self::idOf(...), 'author.name')
        );
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
}
