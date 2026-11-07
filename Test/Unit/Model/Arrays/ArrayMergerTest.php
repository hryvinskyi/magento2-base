<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Arrays\ArrayInspector;
use Hryvinskyi\Base\Model\Arrays\ArrayMerger;
use Hryvinskyi\Base\Model\Arrays\ReplaceArrayValue;
use Hryvinskyi\Base\Model\Arrays\UnsetArrayValue;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperCollection;
use PHPUnit\Framework\TestCase;

class ArrayMergerTest extends TestCase
{
    /**
     * @var ArrayMerger the service under test, built with its real collaborators
     */
    private ArrayMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new ArrayMerger(new ArrayInspector());
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
        $result = $this->merger->merge($a, $b, $c);
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
        $merged = $this->merger->merge(
            ['name' => 'a', 'tags' => ['x']],
            new ArrayHelperCollection(['name' => 'b', 'tags' => ['y']])
        );

        $this->assertSame(['name' => 'b', 'tags' => ['x', 'y']], $merged);
    }

    public function testMergeTakesAnObjectByItsPublicProperties(): void
    {
        $object = new \stdClass();
        $object->name = 'b';

        $this->assertSame(['name' => 'b', 0 => 'x'], $this->merger->merge(['name' => 'a'], ['x'], $object));
    }

    public function testMergeRefusesATraversableKeyThatIsNeitherIntegerNorString(): void
    {
        $entries = (static function (): \Generator {
            yield 1.5 => 'x';
        })();

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Only integer and string keys can be merged.');

        $this->merger->merge([], $entries);
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
        $result = $this->merger->merge($a, $b);
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
            'features' => (new ReplaceArrayValue())->withValue([
                'gii',
            ]),
        ];
        $result = $this->merger->merge($a, $b);
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

    public function testMergeWithReplaceUnderAnIntegerKeyReplacesInPlace(): void
    {
        $this->assertSame(
            ['a', 'z'],
            $this->merger->merge(['a', 'b'], [1 => (new ReplaceArrayValue())->withValue('z')])
        );
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
        $result = $this->merger->merge($a, $b);
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
        $this->assertEquals([], $this->merger->merge([], []));
        $this->assertEquals([], $this->merger->merge([], [], []));
    }

    public function testMergeLeavesTheArraysGivenUnchanged(): void
    {
        $first = ['options' => ['a' => 1]];
        $this->merger->merge($first, ['options' => ['b' => 2]]);

        $this->assertSame(['options' => ['a' => 1]], $first);
    }
}
