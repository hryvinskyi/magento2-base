<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Test\Unit\Model;

use Hryvinskyi\Base\Model\ViewModelCacheTags;
use Hryvinskyi\Base\Model\ViewModelRegistry;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\PageCache\Model\Config as PageCacheConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Base\Model\ViewModelRegistry
 * @covers \Hryvinskyi\Base\Model\ViewModelCacheTags
 */
class ViewModelRegistryTest extends TestCase
{
    /**
     * @var ObjectManagerInterface&MockObject
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var PageCacheConfig&MockObject
     */
    private PageCacheConfig $pageCacheConfig;

    /**
     * @var ViewModelCacheTags
     */
    private ViewModelCacheTags $cacheTags;

    /**
     * @var ViewModelRegistry
     */
    private ViewModelRegistry $registry;

    /**
     * @var ArgumentInterface&IdentityInterface
     */
    private ArgumentInterface $viewModel;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->pageCacheConfig = $this->createMock(PageCacheConfig::class);
        $this->cacheTags = new ViewModelCacheTags();
        $this->registry = new ViewModelRegistry($this->objectManager, $this->cacheTags, $this->pageCacheConfig);
        $this->viewModel = new class implements ArgumentInterface, IdentityInterface {
            /**
             * @return string[]
             */
            public function getIdentities(): array
            {
                return ['cms_b_footer', 'cms_b_footer'];
            }
        };
        $this->objectManager->method('get')->willReturn($this->viewModel);
    }

    public function testReturnsTheViewModelAndCollectsItsTags(): void
    {
        self::assertSame($this->viewModel, $this->registry->require($this->viewModel::class));
        self::assertSame(['cms_b_footer'], $this->cacheTags->get());
    }

    public function testTagsOfAViewModelInsideAnEsiBlockAreLeftToTheEsiRecord(): void
    {
        $this->pageCacheConfig->method('getType')->willReturn(PageCacheConfig::VARNISH);
        $esiParent = $this->block(['ttl' => '3600']);
        $child = $this->block([], $esiParent);

        $this->registry->require($this->viewModel::class, $child);

        self::assertSame([], $this->cacheTags->get());
    }

    public function testTagsAreCollectedWhenNoAncestorHasATtl(): void
    {
        $this->pageCacheConfig->method('getType')->willReturn(PageCacheConfig::VARNISH);

        $this->registry->require($this->viewModel::class, $this->block([], $this->block([])));

        self::assertSame(['cms_b_footer'], $this->cacheTags->get());
    }

    public function testTagsAreCollectedWithoutVarnish(): void
    {
        $this->pageCacheConfig->method('getType')->willReturn(PageCacheConfig::BUILT_IN);

        $this->registry->require($this->viewModel::class, $this->block(['ttl' => '3600']));

        self::assertSame(['cms_b_footer'], $this->cacheTags->get());
    }

    public function testAZeroTtlIsNotEsi(): void
    {
        $this->pageCacheConfig->method('getType')->willReturn(PageCacheConfig::VARNISH);

        $this->registry->require($this->viewModel::class, $this->block(['ttl' => '0']));

        self::assertSame(['cms_b_footer'], $this->cacheTags->get());
    }

    public function testAnObjectThatIsNotAViewModelIsRefused(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturn(new \stdClass());
        $registry = new ViewModelRegistry($objectManager, $this->cacheTags, $this->pageCacheConfig);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The class ' . ArgumentInterface::class . ' is not a view model');

        $registry->require(ArgumentInterface::class);
    }

    /**
     * Builds a block whose data and parent are the given ones.
     *
     * @param array<string, string> $data
     * @param AbstractBlock|null $parent
     * @return AbstractBlock&MockObject
     */
    private function block(array $data, ?AbstractBlock $parent = null): AbstractBlock
    {
        $block = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getParentBlock'])
            ->getMock();
        $block->method('getData')->willReturnCallback(
            static fn (string $key = '') => $data[$key] ?? null
        );
        $block->method('getParentBlock')->willReturn($parent ?? false);

        return $block;
    }
}
