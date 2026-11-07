<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Api\Data\ArrayableInterface;
use Hryvinskyi\Base\Model\Arrays\ArrayKeyNormalizer;
use Hryvinskyi\Base\Model\Arrays\ArrayPathReader;
use Hryvinskyi\Base\Model\Arrays\ObjectToArrayConverter;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperCollection;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperPost1;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperPost2;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperPost3;
use PHPUnit\Framework\TestCase;

class ObjectToArrayConverterTest extends TestCase
{
    /**
     * @var ObjectToArrayConverter the service under test, built with its real collaborators
     */
    private ObjectToArrayConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ObjectToArrayConverter(new ArrayPathReader(new ArrayKeyNormalizer()));
    }

    public function testToArray(): void
    {
        $dataArrayable = $this->createMock(ArrayableInterface::class);
        $dataArrayable->method('toArray')->willReturn([]);
        $this->assertEquals([], $this->converter->toArray($dataArrayable));
        $this->assertEquals(['foo'], $this->converter->toArray('foo'));
        $object = new ArrayHelperPost1();
        $this->assertEquals(get_object_vars($object), $this->converter->toArray($object));
        $object = new ArrayHelperPost2();
        $this->assertEquals(get_object_vars($object), $this->converter->toArray($object));
        $object1 = new ArrayHelperPost1();
        $object2 = new ArrayHelperPost2();
        $this->assertEquals([
            get_object_vars($object1),
            get_object_vars($object2),
        ], $this->converter->toArray([
            $object1,
            $object2,
        ]));
        $object = new ArrayHelperPost2();
        $this->assertEquals([
            'id' => 123,
            'secret' => 's',
            '_content' => 'test',
            'length' => 4,
        ], $this->converter->toArray($object, [
            ArrayHelperPost2::class => [
                'id',
                'secret',
                '_content' => 'content',
                'length' => static fn (ArrayHelperPost2 $post): int => strlen($post->content),
            ],
        ]));
        $object = new ArrayHelperPost3();
        $this->assertEquals(get_object_vars($object), $this->converter->toArray($object, [], false));
        $this->assertEquals(
            [
                'id' => 33,
                'subObject' => [
                    'id' => 123,
                    'content' => 'test',
                ],
            ],
            $this->converter->toArray($object)
        );
        //recursive with attributes of object and subobject
        $this->assertEquals([
            'id' => 33,
            'id_plus_1' => 34,
            'subObject' => [
                'id' => 123,
                'id_plus_1' => 124,
            ],
        ], $this->converter->toArray($object, [
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
        ], $this->converter->toArray($object, [
            ArrayHelperPost2::class => [
                'id',
                'id_plus_1' => static fn (ArrayHelperPost2 $post): int => $post->id + 1,
            ],
        ]));
    }

    public function testToArrayHandsTheRecursionFlagToAnArrayableObject(): void
    {
        $arrayable = $this->createMock(ArrayableInterface::class);
        $arrayable->expects($this->once())->method('toArray')->with([], [], false)->willReturn(['id' => 1]);

        $this->assertSame(['id' => 1], $this->converter->toArray($arrayable, [], false));
    }

    public function testToArrayTakesATraversableThroughItsIterator(): void
    {
        $this->assertSame(
            ['a' => 1, 'b' => [2]],
            $this->converter->toArray(new ArrayHelperCollection(['a' => 1, 'b' => [2]]))
        );
    }
}
