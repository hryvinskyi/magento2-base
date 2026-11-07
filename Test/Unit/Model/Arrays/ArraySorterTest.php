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
use Hryvinskyi\Base\Model\Arrays\ArraySorter;
use PHPUnit\Framework\TestCase;

class ArraySorterTest extends TestCase
{
    /**
     * @var ArraySorter the service under test, built with its real collaborators
     */
    private ArraySorter $sorter;

    protected function setUp(): void
    {
        $keys = new ArrayKeyNormalizer();
        $this->sorter = new ArraySorter(new ArrayIndexer(new ArrayPathReader($keys), $keys));
    }

    public function testMultisort(): void
    {
        // empty key
        $this->assertEquals([], $this->sorter->multisort([], ''));
        // single key
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 1],
            ['name' => 'c', 'age' => 2],
        ];
        $original = $array;
        $sorted = $this->sorter->multisort($array, 'name');
        $this->assertSame($original, $array, 'the array given is left unchanged');
        $this->assertEquals(['name' => 'a', 'age' => 1], $sorted[0]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $sorted[1]);
        $this->assertEquals(['name' => 'c', 'age' => 2], $sorted[2]);
        // multiple keys
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 2],
            ['name' => 'a', 'age' => 1],
        ];
        $array = $this->sorter->multisort($array, ['name', 'age']);
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
        $array = $this->sorter->multisort($array, ['name', 'age'], SORT_ASC, [SORT_STRING, SORT_REGULAR]);
        $this->assertEquals(['name' => 'A', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'B', 'age' => 4], $array[1]);
        $this->assertEquals(['name' => 'a', 'age' => 3], $array[2]);
        $this->assertEquals(['name' => 'b', 'age' => 2], $array[3]);
        $array = $this->sorter->multisort(
            $array,
            ['name', 'age'],
            SORT_ASC,
            [SORT_STRING | SORT_FLAG_CASE, SORT_REGULAR]
        );
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
        $models = $this->sorter->multisort($models, 'type', SORT_ASC);
        $this->assertEquals($obj2, $models[0]);
        $this->assertEquals($obj3, $models[1]);
        $this->assertEquals($obj1, $models[2]);
        $models = $this->sorter->multisort($models, 'type', SORT_DESC);
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
        $array = $this->sorter->multisort($array, ['name'], [SORT_ASC]);
        $this->assertEquals(['name' => 'a', 'age' => 1], $array[0]);
        $this->assertEquals(['name' => 'b', 'age' => 3], $array[1]);
        $this->assertEquals(['name' => 'c', 'age' => 2], $array[2]);

        // multiple keys
        $array = [
            ['name' => 'b', 'age' => 3],
            ['name' => 'a', 'age' => 2],
            ['name' => 'a', 'age' => 1],
        ];
        $array = $this->sorter->multisort($array, ['name', 'age'], [SORT_ASC, SORT_DESC]);
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
        $changelog = $this->sorter->multisort($changelog, function (string $line) use (&$i): string {
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

    public function testMultisortKeepsTheOriginalOrderOfEqualRows(): void
    {
        $array = [
            ['name' => 'b', 'n' => 1],
            ['name' => 'a', 'n' => 2],
            ['name' => 'b', 'n' => 3],
            ['name' => 'a', 'n' => 4],
        ];

        $this->assertSame(
            [2, 4, 1, 3],
            array_column($this->sorter->multisort($array, 'name'), 'n')
        );
    }

    public function testMultisortInvalidParamExceptionDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The length of $direction parameter must be the same as that of $keys.');
        $this->sorter->multisort(['foo' => 'bar'], ['foo'], []);
    }

    public function testMultisortInvalidParamExceptionSortFlag(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The length of $sortFlag parameter must be the same as that of $keys.');
        $this->sorter->multisort(['foo' => 'bar'], ['foo'], [SORT_ASC], []);
    }
}
