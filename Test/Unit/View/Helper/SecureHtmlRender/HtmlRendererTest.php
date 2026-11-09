<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\View\Helper\SecureHtmlRender;

use Hryvinskyi\Base\Test\Unit\Model\Html\Fixture\HtmlCoreServices;
use Hryvinskyi\Base\View\Helper\SecureHtmlRender\HtmlRenderer;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRender\TagData;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\View\Helper\SecureHtmlRender\HtmlRenderer
 */
class HtmlRendererTest extends TestCase
{
    /**
     * @var HtmlRenderer
     */
    private HtmlRenderer $renderer;

    protected function setUp(): void
    {
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES)
        );
        $this->renderer = new HtmlRenderer($escaper, (new HtmlCoreServices())->tagRenderer());
    }

    public function testTextContentIsEscaped(): void
    {
        self::assertSame(
            '<p class="note">a &lt;b&gt;</p>',
            $this->renderer->renderTag(new TagData('p', ['class' => 'note'], 'a <b>', true))
        );
    }

    public function testMarkupContentIsPrintedAsGiven(): void
    {
        self::assertSame(
            '<script type="text/javascript">if (a < b) {}</script>',
            $this->renderer->renderTag(new TagData('script', ['type' => 'text/javascript'], 'if (a < b) {}', false))
        );
    }

    public function testATagWithoutContentIsClosed(): void
    {
        self::assertSame(
            '<script src="/a.js"></script>',
            $this->renderer->renderTag(new TagData('script', ['src' => '/a.js'], null, false))
        );
    }

    public function testANullAttributeIsRenderedWithoutAValue(): void
    {
        $tagData = $this->createMock(TagData::class);
        $tagData->method('getTag')->willReturn('script');
        $tagData->method('getAttributes')->willReturn(
            ['data-ignore-extreme-lazy-load' => null, 'src' => '/a.js', 'data-pagespeed-ignore-merge' => null]
        );
        $tagData->method('getContent')->willReturn(null);
        $tagData->method('isTextContent')->willReturn(false);

        self::assertSame(
            '<script src="/a.js" data-ignore-extreme-lazy-load data-pagespeed-ignore-merge></script>',
            $this->renderer->renderTag($tagData)
        );
    }

    public function testATagWithoutContentRendersEmptyWhenContentIsText(): void
    {
        self::assertSame(
            '<p class="note"></p>',
            $this->renderer->renderTag(new TagData('p', ['class' => 'note'], null, true))
        );
    }

    public function testAVoidTagSelfCloses(): void
    {
        self::assertSame(
            '<link href="/a.css" rel="stylesheet" />',
            $this->renderer->renderTag(new TagData('link', ['rel' => 'stylesheet', 'href' => '/a.css'], null, false))
        );
    }
}
