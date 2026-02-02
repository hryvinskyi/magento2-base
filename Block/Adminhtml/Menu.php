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
    private const DEFAULT_ICON = '<svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"/></svg>';

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
