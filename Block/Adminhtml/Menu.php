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
 *
 * Items come from the "items" layout argument. An item whose "resource" names an ACL resource the current
 * admin user is not allowed is left out; an item without "resource" is always listed. A malformed item is
 * logged and left out instead of breaking the page.
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
     * @var list<MenuItemInterface>|null
     */
    private ?array $sortedItems = null;

    /**
     * @param Context $context
     * @param MenuItemFactoryInterface $menuItemFactory
     * @param array<string,mixed> $data
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
        return $this->readText('menu_title');
    }

    /**
     * Get menu icon
     *
     * @return string
     */
    public function getMenuIcon(): string
    {
        $icon = $this->readText('menu_icon');

        return $icon !== '' ? $icon : self::DEFAULT_ICON;
    }

    /**
     * Get the menu items the current admin user may see, sorted by sort order
     *
     * @return list<MenuItemInterface>
     */
    public function getMenuItems(): array
    {
        if ($this->sortedItems !== null) {
            return $this->sortedItems;
        }

        $items = [];
        foreach ($this->readItemsConfig() as $name => $itemData) {
            $item = $this->createItem((string) $name, $itemData);
            if ($item !== null && $this->isAllowed($item)) {
                $items[] = $item;
            }
        }

        $this->sortedItems = $this->sortItems($items);

        return $this->sortedItems;
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

    /**
     * Read a text layout argument; anything that is not text reads as an empty string
     *
     * @param string $key
     * @return string
     */
    private function readText(string $key): string
    {
        $value = $this->getData($key);

        if (is_string($value)) {
            return $value;
        }

        return $value instanceof \Stringable ? (string) $value : '';
    }

    /**
     * Read the "items" layout argument
     *
     * @return array<mixed>
     */
    private function readItemsConfig(): array
    {
        $itemsConfig = $this->getData('items');

        if ($itemsConfig === null) {
            return [];
        }

        if (!is_array($itemsConfig)) {
            $this->_logger->warning(
                'Admin menu block ignores its "items" argument: an array is expected.',
                ['block' => $this->getNameInLayout(), 'given' => get_debug_type($itemsConfig)]
            );

            return [];
        }

        return $itemsConfig;
    }

    /**
     * Build one menu item, or log and return null when its configuration is malformed
     *
     * @param string $name
     * @param mixed $itemData
     * @return MenuItemInterface|null
     */
    private function createItem(string $name, mixed $itemData): ?MenuItemInterface
    {
        if (!is_array($itemData)) {
            $this->logSkippedItem($name, 'the item configuration must be an array');

            return null;
        }

        $data = [];
        foreach ($itemData as $key => $value) {
            $data[(string) $key] = $value;
        }

        try {
            return $this->menuItemFactory->create($data);
        } catch (\InvalidArgumentException $exception) {
            $this->logSkippedItem($name, $exception->getMessage());

            return null;
        }
    }

    /**
     * Check whether the current admin user may see the item
     *
     * @param MenuItemInterface $item
     * @return bool
     */
    private function isAllowed(MenuItemInterface $item): bool
    {
        $resource = $item->getResource();

        return $resource === null || $this->getAuthorization()->isAllowed($resource);
    }

    /**
     * Log a menu item left out because of its configuration
     *
     * @param string $name
     * @param string $reason
     * @return void
     */
    private function logSkippedItem(string $name, string $reason): void
    {
        $this->_logger->warning(
            'Admin menu item skipped: ' . $reason,
            ['block' => $this->getNameInLayout(), 'item' => $name]
        );
    }

    /**
     * Sort menu items by sort order; items with the same sort order keep their configured order
     *
     * @param list<MenuItemInterface> $items
     * @return list<MenuItemInterface>
     */
    private function sortItems(array $items): array
    {
        usort($items, static function (MenuItemInterface $a, MenuItemInterface $b): int {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        return $items;
    }
}
