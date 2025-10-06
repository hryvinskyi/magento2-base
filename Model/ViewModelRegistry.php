<?php
/**
 * Copyright (c) 2024. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\PageCache\Model\Config as PageCacheConfig;

/**
 * A registry that can return instances of any view model. They no longer need to be passed to each block via layout XML
 *
 * Available in templates as `$viewModels`. Uses the object manager internally, no need to duplicate its instance cache.
 */
class ViewModelRegistry
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ViewModelCacheTags
     */
    private $viewModelCacheTags;

    /**
     * @var PageCacheConfig
     */
    private $pageCacheConfig;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ViewModelCacheTags $viewModelCacheTags,
        PageCacheConfig $pageCacheConfig
    ) {
        $this->objectManager = $objectManager;
        $this->viewModelCacheTags = $viewModelCacheTags;
        $this->pageCacheConfig = $pageCacheConfig;
    }

    /**
     * Returns view model instance for given FQN
     *
     * @template T of ArgumentInterface
     * @param class-string<T> $viewModelClass Fully qualified class name (FQN)
     * @param AbstractBlock|null $block Only required if view model is used within a template cached in ESI (ttl="n" in layout XML)
     * @return ArgumentInterface
     * @throws LocalizedException If class not found or not a view model
     * @phpstan-return T
     */
    public function require(string $viewModelClass, AbstractBlock $block = null): ArgumentInterface
    {
        try {
            $object = $this->objectManager->get($viewModelClass);
        } catch (\Exception $e) {
            throw new LocalizedException(__('The class %1 could not be instantiated. Exception: %2', $viewModelClass, $e->getMessage()));
        }
        if (!$object instanceof ArgumentInterface) {
            throw new LocalizedException(__('The class %1 is not a view model', $viewModelClass));
        }

        // We do not want to collect the cache tags for blocks that will be served via ESI while rendering the main page FPC record.
        // If we do, the main page will be purged for those tags, even though we only want to purge the ESI records.
        // On ESI requests isVarnishEnabled() is false, so we don't need to check for a ttl value.
        // If isVarnishEnabled() is true, this is a main page request (not ESI), so we check if the block is rendered within an ESI section.
        if (!$block || !($this->isVarnishEnabled() && $this->isCalledWithinEsiBlock($block))) {
            $this->viewModelCacheTags->collectFrom($object);
        }

        return $object;
    }

    private function isCalledWithinEsiBlock(AbstractBlock $block): bool
    {
        while ($block instanceof AbstractBlock && ! $block->getTtl()) {
            $block = $block->getParentBlock();
        }
        return $block && $block->getTtl() > 0;
    }

    private function isVarnishEnabled(): bool
    {
        return $this->pageCacheConfig->getType() === PageCacheConfig::VARNISH;
    }
}
