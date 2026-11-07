<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model\Html;

use Hryvinskyi\Base\Exception\InvalidParamException;
use Hryvinskyi\Base\Model\Html\ResourceTagRenderer;
use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlElementServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\Html\ResourceTagRenderer
 */
class ResourceTagRendererTest extends TestCase
{
    /**
     * @var ResourceTagRenderer
     */
    private ResourceTagRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = (new HtmlElementServices())->resourceTagRenderer();
    }

    public function testStyle(): void
    {
        $content = 'a <>';
        self::assertSame("<style>{$content}</style>", $this->renderer->style($content));
        self::assertSame(
            "<style type=\"text/less\">{$content}</style>",
            $this->renderer->style($content, ['type' => 'text/less'])
        );
    }

    public function testScript(): void
    {
        $content = 'a <>';
        self::assertSame("<script>{$content}</script>", $this->renderer->script($content));
        self::assertSame(
            "<script type=\"text/js\">{$content}</script>",
            $this->renderer->script($content, ['type' => 'text/js'])
        );
    }

    public function testCssFile(): void
    {
        $link = '<link href="http://example.com" rel="stylesheet" />';
        self::assertSame($link, $this->renderer->cssFile('http://example.com'));
        self::assertSame('<link href="/test" rel="stylesheet" />', $this->renderer->cssFile('/test'));
        self::assertSame(
            "<!--[if IE 9]>\n" . $link . "\n<![endif]-->",
            $this->renderer->cssFile('http://example.com', ['condition' => 'IE 9'])
        );
        self::assertSame(
            "<!--[if (gte IE 9)|(!IE)]><!-->\n" . $link . "\n<!--<![endif]-->",
            $this->renderer->cssFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)'])
        );
        self::assertSame(
            '<noscript>' . $link . '</noscript>',
            $this->renderer->cssFile('http://example.com', ['noscript' => true])
        );
    }

    public function testJsFile(): void
    {
        $script = '<script src="http://example.com"></script>';
        self::assertSame($script, $this->renderer->jsFile('http://example.com'));
        self::assertSame('<script src="/test"></script>', $this->renderer->jsFile('/test'));
        self::assertSame(
            "<!--[if IE 9]>\n" . $script . "\n<![endif]-->",
            $this->renderer->jsFile('http://example.com', ['condition' => 'IE 9'])
        );
        self::assertSame(
            "<!--[if (gte IE 9)|(!IE)]><!-->\n" . $script . "\n<!--<![endif]-->",
            $this->renderer->jsFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)'])
        );
    }

    public function testCssFileKeepsAGivenRel(): void
    {
        self::assertSame(
            '<link href="/a.css" rel="preload" />',
            $this->renderer->cssFile('/a.css', ['rel' => 'preload'])
        );
    }

    public function testCssFileConditionTakesPriorityOverNoscript(): void
    {
        self::assertSame(
            "<!--[if IE 9]>\n" . '<link href="/a.css" rel="stylesheet" noscript />' . "\n<![endif]-->",
            $this->renderer->cssFile('/a.css', ['condition' => 'IE 9', 'noscript' => true])
        );
    }

    public function testCssFileTreatsOnlyTrueAsNoscript(): void
    {
        self::assertSame(
            '<link href="/a.css" rel="stylesheet" />',
            $this->renderer->cssFile('/a.css', ['noscript' => false])
        );
        self::assertSame(
            '<link href="/a.css" rel="stylesheet" noscript="1" />',
            $this->renderer->cssFile('/a.css', ['noscript' => '1'])
        );
    }

    public function testCssFileRefusesAConditionWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->cssFile('/a.css', ['condition' => ['IE 9']]);
    }

    public function testJsFileRefusesAConditionWithoutTextForm(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->renderer->jsFile('/a.js', ['condition' => new \stdClass()]);
    }
}
