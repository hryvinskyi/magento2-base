<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Plugin;

use Hryvinskyi\Base\Plugin\Layout;
use Hryvinskyi\Base\Test\Unit\Fake\FixedLayoutDebugConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Layout as ViewLayout;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Plugin\Layout
 */
class LayoutTest extends TestCase
{
    /**
     * Each debug switch, request and core answer gives the expected cacheability.
     *
     * @return array<string, array{bool, array<string, string>, bool, bool}>
     */
    public static function cacheabilityProvider(): array
    {
        return [
            'debug on, xml asked' => [true, ['xml' => '1'], true, false],
            'debug on, hints asked' => [true, ['hints' => '1'], true, false],
            'debug on, nothing asked' => [true, [], true, true],
            'debug off, xml asked' => [false, ['xml' => '1'], true, true],
            'debug off, hints asked' => [false, ['hints' => '1'], true, true],
            'an uncacheable layout stays uncacheable' => [false, [], false, false],
        ];
    }

    /**
     * Only the debug being on together with a request for its output turns the page cache off.
     *
     * @param bool $debugEnabled whether the layout debug is on
     * @param array<string, string> $params the request parameters
     * @param bool $result the core answer
     * @param bool $expected the expected answer
     * @return void
     */
    #[DataProvider('cacheabilityProvider')]
    public function testCacheability(bool $debugEnabled, array $params, bool $result, bool $expected): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $key): ?string => $params[$key] ?? null
        );
        $plugin = new Layout($request, new FixedLayoutDebugConfig($debugEnabled));

        self::assertSame($expected, $plugin->afterIsCacheable($this->createMock(ViewLayout::class), $result));
    }
}
