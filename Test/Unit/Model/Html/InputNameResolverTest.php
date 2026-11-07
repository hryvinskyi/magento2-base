<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\InputNameResolver;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\InputNameResolver
 */
class InputNameResolverTest extends TestCase
{
    /**
     * Attribute expressions and the names inside them.
     *
     * @return list<array{string, string}>
     */
    public static function validAttributeNamesProvider(): array
    {
        return [
            ['asd]asdf.asdfa[asdfa', 'asdf.asdfa'],
            ['a', 'a'],
            ['[0]a', 'a'],
            ['a[0]', 'a'],
            ['[0]a[0]', 'a'],
            ['[0]a.[0]', 'a.'],
            // Unicode checks.
            ['ä', 'ä'],
            ['ä', 'ä'],
            ['asdf]öáöio..[asdfasdf', 'öáöio..'],
            ['öáöio', 'öáöio'],
            ['[0]test.ööößß.d', 'test.ööößß.d'],
            ['ИІК', 'ИІК'],
            [']ИІК[', 'ИІК'],
            ['[0]ИІК[0]', 'ИІК'],
        ];
    }

    /**
     * Attribute expressions that hold no valid name.
     *
     * @return list<array{string}>
     */
    public static function invalidAttributeNamesProvider(): array
    {
        return [
            ['. ..'],
            ['a +b'],
            ['a,b'],
        ];
    }

    /**
     * Input names and the element ids derived from them.
     *
     * @return array<string, array{string, string}>
     */
    public static function inputIdsProvider(): array
    {
        return [
            'plain' => ['name', 'name'],
            'array element' => ['Post[content]', 'post-content'],
            'nested' => ['Post[a][b]', 'post-a-b'],
            'array input' => ['tags[]', 'tags'],
            'space and dot' => ['a b.c', 'a-b-c'],
            'double dash' => ['a--b', 'a-b'],
            'multibyte' => ['ИМЯ[Ä]', 'имя-ä'],
        ];
    }

    /**
     * @param string $name
     * @param string $expected
     */
    #[DataProvider('validAttributeNamesProvider')]
    public function testAttributeNameValidation(string $name, string $expected): void
    {
        self::assertSame($expected, (new HtmlCoreServices())->inputNameResolver()->getAttributeName($name));
    }

    /**
     * @param string $name
     */
    #[DataProvider('invalidAttributeNamesProvider')]
    public function testAttributeNameException(string $name): void
    {
        $this->expectException(InvalidParamException::class);

        (new HtmlCoreServices())->inputNameResolver()->getAttributeName($name);
    }

    public function testConfiguredRegexReplacesTheDefault(): void
    {
        $resolver = new InputNameResolver('/^(x)(\d+)$/');

        self::assertSame('12', $resolver->getAttributeName('x12'));
    }

    public function testAConfiguredRegexWithoutANameGroupIsRefused(): void
    {
        $this->expectException(InvalidParamException::class);

        (new InputNameResolver('/^a$/'))->getAttributeName('a');
    }

    /**
     * @param string $name
     * @param string $expected
     */
    #[DataProvider('inputIdsProvider')]
    public function testGetInputIdByName(string $name, string $expected): void
    {
        self::assertSame($expected, (new HtmlCoreServices())->inputNameResolver()->getInputIdByName($name));
    }
}
