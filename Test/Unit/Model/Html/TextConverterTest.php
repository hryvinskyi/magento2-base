<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\TextConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\TextConverter
 */
class TextConverterTest extends TestCase
{
    /**
     * Values with a text form and that text.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function textProvider(): array
    {
        return [
            'string' => ['a', 'a'],
            'integer' => [1, '1'],
            'float' => [1.5, '1.5'],
            'true' => [true, '1'],
            'false' => [false, ''],
            'null' => [null, ''],
        ];
    }

    /**
     * Values without a text form.
     *
     * @return array<string, array{mixed}>
     */
    public static function noTextFormProvider(): array
    {
        return [
            'array' => [['a']],
            'plain object' => [new \stdClass()],
        ];
    }

    /**
     * @param mixed $value
     * @param string $expected
     */
    #[DataProvider('textProvider')]
    public function testToText(mixed $value, string $expected): void
    {
        self::assertSame($expected, (new TextConverter())->toText($value));
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('noTextFormProvider')]
    public function testToTextRefusesAValueWithoutTextForm(mixed $value): void
    {
        $this->expectException(InvalidParamException::class);

        (new TextConverter())->toText($value);
    }

    public function testTextsKeepsTheKeys(): void
    {
        self::assertSame(['a' => '1', 3 => 'b'], (new TextConverter())->texts(['a' => 1, 3 => 'b']));
    }
}
