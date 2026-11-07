<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\CssOptions;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\CssOptions
 */
class CssOptionsTest extends TestCase
{
    /**
     * @var CssOptions
     */
    private CssOptions $cssOptions;

    protected function setUp(): void
    {
        $this->cssOptions = (new HtmlCoreServices())->cssOptions();
    }

    public function testAddCssClass(): void
    {
        $options = $this->cssOptions->addCssClass([], 'test');
        self::assertSame(['class' => 'test'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test');
        self::assertSame(['class' => 'test'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test2');
        self::assertSame(['class' => 'test test2'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test');
        self::assertSame(['class' => 'test test2'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test2');
        self::assertSame(['class' => 'test test2'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test3');
        self::assertSame(['class' => 'test test2 test3'], $options);
        $options = $this->cssOptions->addCssClass($options, 'test2');
        self::assertSame(['class' => 'test test2 test3'], $options);

        $options = $this->cssOptions->addCssClass(['class' => ['test']], 'test2');
        self::assertSame(['class' => ['test', 'test2']], $options);
        $options = $this->cssOptions->addCssClass($options, 'test2');
        self::assertSame(['class' => ['test', 'test2']], $options);
        $options = $this->cssOptions->addCssClass($options, ['test3']);
        self::assertSame(['class' => ['test', 'test2', 'test3']], $options);

        self::assertSame(
            ['class' => 'test test1 test2'],
            $this->cssOptions->addCssClass(['class' => 'test'], ['test1', 'test2'])
        );
    }

    public function testAddCssClassStoresTheGivenValueWhenThereIsNoClassYet(): void
    {
        self::assertSame(['class' => ['a', 'b']], $this->cssOptions->addCssClass([], ['a', 'b']));
        self::assertSame(['class' => 'a'], $this->cssOptions->addCssClass(['class' => null], 'a'));
    }

    public function testAddCssClassLeavesTheGivenOptionsUntouched(): void
    {
        $options = ['class' => 'a'];

        $this->cssOptions->addCssClass($options, 'b');

        self::assertSame(['class' => 'a'], $options);
    }

    public function testMergeCssClass(): void
    {
        $options = $this->cssOptions->addCssClass(['class' => ['persistent' => 'test1']], ['persistent' => 'test2']);
        self::assertSame(['persistent' => 'test1'], $options['class']);

        $options = $this->cssOptions->addCssClass($options, ['additional' => 'test2']);
        self::assertSame(['persistent' => 'test1', 'additional' => 'test2'], $options['class']);
    }

    public function testAddCssClassRemovesExistingDuplicatesWhenNormalisationIsOn(): void
    {
        self::assertSame(['class' => 'a b'], $this->cssOptions->addCssClass(['class' => 'a a'], 'b'));
        self::assertSame(
            ['class' => [0 => 'a', 2 => 'b']],
            $this->cssOptions->addCssClass(['class' => ['a', 'a']], 'b')
        );
    }

    public function testAddCssClassKeepsDuplicatesWhenNormalisationIsOff(): void
    {
        $cssOptions = (new HtmlCoreServices())->cssOptions(false);

        self::assertSame(['class' => 'a a b'], $cssOptions->addCssClass(['class' => 'a a'], 'b'));
        self::assertSame(['class' => ['a', 'a', 'b']], $cssOptions->addCssClass(['class' => ['a', 'a']], 'b'));
    }

    public function testAddCssClassRefusesAnExistingClassWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);

        $this->cssOptions->addCssClass(['class' => new \stdClass()], 'a');
    }

    public function testRemoveCssClass(): void
    {
        $options = $this->cssOptions->removeCssClass(['class' => 'test test2 test3'], 'test2');
        self::assertSame(['class' => 'test test3'], $options);
        $options = $this->cssOptions->removeCssClass($options, 'test2');
        self::assertSame(['class' => 'test test3'], $options);
        $options = $this->cssOptions->removeCssClass($options, 'test');
        self::assertSame(['class' => 'test3'], $options);
        $options = $this->cssOptions->removeCssClass($options, 'test3');
        self::assertSame([], $options);

        $options = $this->cssOptions->removeCssClass(['class' => ['test', 'test2', 'test3']], 'test2');
        self::assertSame(['class' => [0 => 'test', 2 => 'test3']], $options);
        $options = $this->cssOptions->removeCssClass($options, 'test');
        $options = $this->cssOptions->removeCssClass($options, 'test3');
        self::assertSame([], $options);

        self::assertSame(
            ['class' => 'test'],
            $this->cssOptions->removeCssClass(['class' => 'test test1 test2'], ['test1', 'test2'])
        );
    }

    public function testRemoveCssClassWithoutAClassReturnsTheOptions(): void
    {
        self::assertSame(['id' => 'a'], $this->cssOptions->removeCssClass(['id' => 'a'], 'b'));
    }

    public function testCssStyleFromArray(): void
    {
        self::assertSame('width: 100px; height: 200px;', $this->cssOptions->cssStyleFromArray([
            'width' => '100px',
            'height' => '200px',
        ]));
        self::assertNull($this->cssOptions->cssStyleFromArray([]));
    }

    public function testCssStyleFromArrayRefusesAValueWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);

        $this->cssOptions->cssStyleFromArray(['width' => ['100px']]);
    }

    public function testCssStyleToArray(): void
    {
        self::assertSame(
            ['width' => '100px', 'height' => '200px'],
            $this->cssOptions->cssStyleToArray('width: 100px; height: 200px;')
        );
        self::assertSame([], $this->cssOptions->cssStyleToArray('  '));
    }

    public function testAddCssStyle(): void
    {
        $style = ['style' => 'width: 100px; height: 200px;'];

        self::assertSame(
            'width: 110px; height: 200px; color: red;',
            $this->cssOptions->addCssStyle($style, 'width: 110px; color: red;')['style']
        );
        self::assertSame(
            'width: 110px; height: 200px; color: red;',
            $this->cssOptions->addCssStyle($style, ['width' => '110px', 'color' => 'red'])['style']
        );
        self::assertSame(
            'width: 100px; height: 200px; color: red;',
            $this->cssOptions->addCssStyle($style, 'width: 110px; color: red;', false)['style']
        );
        self::assertSame(
            'width: 110px; color: red;',
            $this->cssOptions->addCssStyle([], 'width: 110px; color: red;')['style']
        );
        self::assertSame(
            'width: 110px; color: red;',
            $this->cssOptions->addCssStyle([], 'width: 110px; color: red;', false)['style']
        );
        self::assertSame(
            'width: 100px; color: red;',
            $this->cssOptions->addCssStyle(['style' => ['width' => '100px']], ['color' => 'red'], false)['style']
        );
    }

    public function testRemoveCssStyle(): void
    {
        $options = $this->cssOptions->removeCssStyle(['style' => 'width: 110px; height: 200px; color: red;'], 'width');
        self::assertSame('height: 200px; color: red;', $options['style']);
        $options = $this->cssOptions->removeCssStyle($options, ['height']);
        self::assertSame('color: red;', $options['style']);
        $options = $this->cssOptions->removeCssStyle($options, ['color', 'background']);
        self::assertArrayHasKey('style', $options);
        self::assertNull($options['style']);

        self::assertArrayNotHasKey('style', $this->cssOptions->removeCssStyle([], ['color', 'background']));

        self::assertSame(
            'width: 100px;',
            $this->cssOptions->removeCssStyle(['style' => ['color' => 'red', 'width' => '100px']], ['color'])['style']
        );
    }
}
