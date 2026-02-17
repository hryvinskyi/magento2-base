<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Block\Adminhtml;

use Hryvinskyi\Base\Api\Menu\MenuItemFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Extensible admin menu block with layout XML configuration support
 */
class Menu extends Template
{
    /**
     * Default hamburger icon SVG
     */
    private const DEFAULT_ICON = '<svg class="hamburger-icon" width="14" height="14" viewBox="0 0 18 18" fill="none">
            <line x1="1" y1="3" x2="17" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line>
            <line x1="1" y1="9" x2="17" y2="9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line>
            <line x1="1" y1="15" x2="17" y2="15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line>
        </svg>';

    /**
     * @var string
     */
    protected $_template = 'Hryvinskyi_Base::menu.phtml';

    /**
     * @var MenuItemInterface[]|null
     */
    private ?array $sortedItems = null;

    /**
     * @param Context $context
     * @param MenuItemFactoryInterface $menuItemFactory
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly MenuItemFactoryInterface $menuItemFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get menu title
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return (string) $this->getData('menu_title');
    }

    /**
     * Get menu icon
     *
     * @return string
     */
    public function getMenuIcon(): string
    {
        $icon = $this->getData('menu_icon');

        return $icon !== null && $icon !== '' ? (string) $icon : self::DEFAULT_ICON;
    }

    /**
     * Get menu items sorted by sort order
     *
     * @return MenuItemInterface[]
     */
    public function getMenuItems(): array
    {
        if ($this->sortedItems !== null) {
            return $this->sortedItems;
        }

        $itemsConfig = $this->getData('items') ?? [];
        $items = [];

        foreach ($itemsConfig as $itemData) {
            if (is_array($itemData)) {
                $items[] = $this->menuItemFactory->create($itemData);
            }
        }

        $this->sortedItems = $this->sortItems($items);

        return $this->sortedItems;
    }

    /**
     * Sort menu items by sort order
     *
     * @param MenuItemInterface[] $items
     * @return MenuItemInterface[]
     */
    private function sortItems(array $items): array
    {
        usort($items, static function (MenuItemInterface $a, MenuItemInterface $b): int {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        return $items;
    }

    /**
     * Check if menu has items
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return count($this->getMenuItems()) > 0;
    }
}
