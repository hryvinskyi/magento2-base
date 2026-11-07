<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Arrays;

use Hryvinskyi\Base\Model\Arrays\ArrayKeyNormalizer;
use Hryvinskyi\Base\Model\Arrays\ArrayPathReader;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperCollection;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperPost1;
use Hryvinskyi\Base\Test\Unit\Model\Arrays\Fixture\ArrayHelperPost2;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayPathReaderTest extends TestCase
{
    /**
     * @var ArrayPathReader the service under test, built with its real collaborators
     */
    private ArrayPathReader $reader;

    protected function setUp(): void
    {
        $this->reader = new ArrayPathReader(new ArrayKeyNormalizer());
    }

    /**
     * A float key finds the integer key PHP stores it under.
     *
     * @see https://github.com/yiisoft/yii2/pull/11549
     */
    public function testGetValueWithFloatKey(): void
    {
        $array = [1 => 'some value'];
        $result = $this->reader->getValue($array, 1.0);
        $this->assertEquals('some value', $result);
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
        $this->assertEquals($expected, $this->reader->getValue($array, $key, $default));
    }

    public function testGetValueObjects(): void
    {
        $collection = new ArrayHelperCollection(['id' => 23]);
        $this->assertEquals(23, $this->reader->getValue($collection, 'id'));
        $object = new ArrayHelperPost1();
        $this->assertEquals(23, $this->reader->getValue($object, 'id'));
    }

    public function testGetValueNonexistingProperties1(): void
    {
        $object = new ArrayHelperPost1();
        [$value, $warnings] = $this->captureWarnings(
            fn () => $this->reader->getValue($object, 'nonExisting')
        );
        $this->assertNull($value);
        $this->assertSame(['Undefined property: ' . ArrayHelperPost1::class . '::$nonExisting'], $warnings);
    }

    public function testGetValueNonexistingProperties2(): void
    {
        $collection = new ArrayHelperCollection(['id' => 23]);
        [$value, $warnings] = $this->captureWarnings(
            fn () => $this->reader->getValue($collection, 'nonExisting')
        );
        $this->assertNull($value);
        $this->assertSame(['Undefined array key "nonExisting"'], $warnings);
    }

    public function testGetValueRethrowsWhatGetThrowsOnAnObjectWithoutArrayAccess(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Getting unknown property: ' . ArrayHelperPost2::class . '::unknown');

        $this->reader->getValue(new ArrayHelperPost2(), 'unknown', 'default');
    }

    public function testGetValueReadsAPropertyThroughGet(): void
    {
        $this->assertSame('s', $this->reader->getValue(new ArrayHelperPost2(), 'secret'));
    }

    public function testKeyExists(): void
    {
        $array = [
            'a' => 1,
            'B' => 2,
        ];
        $this->assertTrue($this->reader->keyExists('a', $array));
        $this->assertFalse($this->reader->keyExists('b', $array));
        $this->assertTrue($this->reader->keyExists('B', $array));
        $this->assertFalse($this->reader->keyExists('c', $array));
        $this->assertTrue($this->reader->keyExists('a', $array, false));
        $this->assertTrue($this->reader->keyExists('b', $array, false));
        $this->assertTrue($this->reader->keyExists('B', $array, false));
        $this->assertFalse($this->reader->keyExists('c', $array, false));
    }

    public function testKeyExistsFindsAKeyHoldingNullAndAsksAnArrayAccessObject(): void
    {
        $this->assertTrue($this->reader->keyExists('a', ['a' => null]));
        $this->assertTrue($this->reader->keyExists('id', new ArrayHelperCollection(['id' => null])));
        $this->assertFalse($this->reader->keyExists('other', new ArrayHelperCollection(['id' => null])));
    }

    public function testKeyExistsRefusesAnArrayAccessObjectInCaseInsensitiveMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second parameter($array) cannot be ArrayAccess in case insensitive mode');

        $this->reader->keyExists('id', new ArrayHelperCollection(['id' => 1]), false);
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
