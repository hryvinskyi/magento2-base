<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayInspector;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayInspectorTest extends TestCase
{
    /**
     * @var ArrayInspector the service under test, built with its real collaborators
     */
    private ArrayInspector $inspector;

    protected function setUp(): void
    {
        $this->inspector = new ArrayInspector();
    }

    public function testIsAssociative(): void
    {
        $this->assertFalse($this->inspector->isAssociative('test'));
        $this->assertFalse($this->inspector->isAssociative([]));
        $this->assertFalse($this->inspector->isAssociative([1, 2, 3]));
        $this->assertFalse($this->inspector->isAssociative([1], false));
        $this->assertTrue($this->inspector->isAssociative(['name' => 1, 'value' => 'test']));
        $this->assertFalse($this->inspector->isAssociative(['name' => 1, 'value' => 'test', 3]));
        $this->assertTrue($this->inspector->isAssociative(['name' => 1, 'value' => 'test', 3], false));
    }

    public function testIsIndexed(): void
    {
        $this->assertFalse($this->inspector->isIndexed('test'));
        $this->assertTrue($this->inspector->isIndexed([]));
        $this->assertTrue($this->inspector->isIndexed([1, 2, 3]));
        $this->assertTrue($this->inspector->isIndexed([2 => 'a', 3 => 'b']));
        $this->assertFalse($this->inspector->isIndexed([2 => 'a', 3 => 'b'], true));
        $this->assertFalse($this->inspector->isIndexed(['a' => 'b'], false));
    }

    public function testIsIndexedConsecutiveAcceptsAnEmptyArray(): void
    {
        $this->assertTrue($this->inspector->isIndexed([], true));
        $this->assertTrue($this->inspector->isIndexed(['a', 'b'], true));
    }

    public function testIsIn(): void
    {
        $this->assertTrue($this->inspector->isIn('a', new ArrayHelperCollection(['a', 'b'])));
        $this->assertTrue($this->inspector->isIn('a', ['a', 'b']));
        $this->assertTrue($this->inspector->isIn('1', new ArrayHelperCollection([1, 'b'])));
        $this->assertTrue($this->inspector->isIn('1', [1, 'b']));
        $this->assertFalse($this->inspector->isIn('1', new ArrayHelperCollection([1, 'b']), true));
        $this->assertFalse($this->inspector->isIn('1', [1, 'b'], true));
        $this->assertTrue($this->inspector->isIn(['a'], new ArrayHelperCollection([['a'], 'b'])));
        $this->assertFalse($this->inspector->isIn('a', new ArrayHelperCollection([['a'], 'b'])));
        $this->assertFalse($this->inspector->isIn('a', [['a'], 'b']));
    }

    public function testIsInStrict(): void
    {
        // strict comparison
        $this->assertTrue($this->inspector->isIn(1, new ArrayHelperCollection([1, 'a']), true));
        $this->assertTrue($this->inspector->isIn(1, [1, 'a'], true));
        $this->assertFalse($this->inspector->isIn('1', new ArrayHelperCollection([1, 'a']), true));
        $this->assertFalse($this->inspector->isIn('1', [1, 'a'], true));
    }

    public function testInException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument $haystack must be an array or implement Traversable');
        $this->inspector->isIn('value', null);
    }

    public function testIsSubset(): void
    {
        $this->assertTrue($this->inspector->isSubset(['a'], new ArrayHelperCollection(['a', 'b'])));
        $this->assertTrue($this->inspector->isSubset(new ArrayHelperCollection(['a']), ['a', 'b']));
        $this->assertTrue($this->inspector->isSubset([1], new ArrayHelperCollection(['1', 'b'])));
        $this->assertTrue($this->inspector->isSubset(new ArrayHelperCollection([1]), ['1', 'b']));
        $this->assertFalse($this->inspector->isSubset([1], new ArrayHelperCollection(['1', 'b']), true));
        $this->assertFalse($this->inspector->isSubset(new ArrayHelperCollection([1]), ['1', 'b'], true));
    }

    public function testIsSubsetException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument $needles must be an array or implement Traversable');
        $this->inspector->isSubset('a', new ArrayHelperCollection(['a', 'b']));
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
        $this->assertSame($expected, $this->inspector->isTraversable($value));
    }
}
