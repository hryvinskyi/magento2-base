<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Plugin;

use Hryvinskyi\Base\Plugin\DebugHints;
use Hryvinskyi\Base\Test\Unit\Fake\FixedLayoutDebugConfig;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHints as DebugHintsDecorator;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHintsFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\TemplateEngineFactory;
use Magento\Framework\View\TemplateEngineInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Plugin\DebugHints
 */
class DebugHintsTest extends TestCase
{
    /**
     * @var TemplateEngineInterface&MockObject
     */
    private MockObject $engine;

    /**
     * @var DebugHintsFactory&MockObject
     */
    private MockObject $decoratorFactory;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private MockObject $scopeConfig;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private MockObject $storeManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->engine = $this->createMock(TemplateEngineInterface::class);
        $this->decoratorFactory = $this->createMock(DebugHintsFactory::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
    }

    /**
     * The engine stays undecorated, and the store is not read, unless the debug is on and the request asks for hints.
     *
     * @param bool $debugEnabled whether the layout debug is on
     * @param bool $hintsAsked whether the request asks for the hints
     * @return void
     */
    #[TestWith([false, true])]
    #[TestWith([true, false])]
    #[TestWith([false, false])]
    public function testKeepsTheEngineUndecorated(bool $debugEnabled, bool $hintsAsked): void
    {
        $this->decoratorFactory->expects(self::never())->method('create');
        $this->storeManager->expects(self::never())->method('getStore');
        $plugin = $this->plugin($debugEnabled, $hintsAsked ? ['hints' => '1'] : []);

        self::assertSame(
            $this->engine,
            $plugin->afterCreate($this->createMock(TemplateEngineFactory::class), $this->engine)
        );
    }

    /**
     * Whether the store setting and the "hints-block" parameter decide on block hints.
     *
     * @return array<string, array{mixed, array<string, string>, bool}>
     */
    public static function blockHintsProvider(): array
    {
        return [
            'neither' => ['0', ['hints' => '1'], false],
            'the store setting' => ['1', ['hints' => '1'], true],
            'the request parameter' => ['0', ['hints' => '1', 'hints-block' => '1'], true],
        ];
    }

    /**
     * The decorated engine shows block hints when the setting of the current store or the request says so.
     *
     * @param mixed $setting the store setting
     * @param array<string, string> $params the request parameters
     * @param bool $showBlockHints the expected decorator argument
     * @return void
     */
    #[DataProvider('blockHintsProvider')]
    public function testDecoratesTheEngine(mixed $setting, array $params, bool $showBlockHints): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getCode')->willReturn('current');
        $this->storeManager->method('getStore')->willReturn($store);
        $this->scopeConfig->method('getValue')
            ->with(DebugHints::XML_PATH_DEBUG_TEMPLATE_HINTS_BLOCKS, ScopeInterface::SCOPE_STORE, 'current')
            ->willReturn($setting);
        $decorator = $this->createMock(DebugHintsDecorator::class);
        $this->decoratorFactory->expects(self::once())
            ->method('create')
            ->with(['subject' => $this->engine, 'showBlockHints' => $showBlockHints])
            ->willReturn($decorator);

        $result = $this->plugin(true, $params)
            ->afterCreate($this->createMock(TemplateEngineFactory::class), $this->engine);

        self::assertSame($decorator, $result);
    }

    /**
     * Builds the plugin.
     *
     * @param bool $debugEnabled whether the layout debug is on
     * @param array<string, string> $params the request parameters
     * @return DebugHints
     */
    private function plugin(bool $debugEnabled, array $params): DebugHints
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $key): ?string => $params[$key] ?? null
        );

        return new DebugHints(
            $request,
            new FixedLayoutDebugConfig($debugEnabled),
            $this->scopeConfig,
            $this->storeManager,
            $this->decoratorFactory
        );
    }
}
