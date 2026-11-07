<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Block\Adminhtml;

use Hryvinskyi\Base\Api\Menu\MenuGroupFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemFactoryInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;
use Hryvinskyi\Base\Model\Menu\MenuSection;
use Hryvinskyi\Base\Model\Menu\MenuSectionArrangerInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Extensible admin menu block with layout XML configuration support
 *
 * Items come from the "items" layout argument. An item whose "resource" names an ACL resource the current
 * admin user is not allowed is left out; an item without "resource" is always listed. An item is shown when it
 * is allowed and active. A malformed item is logged and left out instead of breaking the page.
 *
 * The optional "groups" argument declares titled groups (code => label, sort_order) that items join through
 * their "group" key; "max_items_per_column" (default 6) splits a long group into columns. A malformed group or
 * column limit is logged and ignored. A menu that declares no group, or whose items name none, renders as a
 * single list.
 */
class Menu extends Template
{
    /**
     * Column limit used when the layout gives none or a malformed one
     */
    private const DEFAULT_MAX_ITEMS_PER_COLUMN = 6;

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
     * @var array<int|string, MenuItemInterface>|null
     */
    private ?array $sortedItems = null;

    /**
     * @var list<MenuSection>|null
     */
    private ?array $sections = null;

    /**
     * @param Context $context
     * @param MenuItemFactoryInterface $menuItemFactory
     * @param MenuGroupFactoryInterface $menuGroupFactory
     * @param MenuSectionArrangerInterface $sectionArranger
     * @param array<string,mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly MenuItemFactoryInterface $menuItemFactory,
        private readonly MenuGroupFactoryInterface $menuGroupFactory,
        private readonly MenuSectionArrangerInterface $sectionArranger,
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
     * Get the menu items the current admin user is allowed, sorted by sort order, inactive ones included
     *
     * @return list<MenuItemInterface>
     */
    public function getMenuItems(): array
    {
        return array_values($this->allowedItems());
    }

    /**
     * Check whether the menu shows at least one item: one that is allowed and active
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->visibleItems() !== [];
    }

    /**
     * Get the shown items laid out as sections (one per group with items, then the untitled rest) of columns
     *
     * @return list<MenuSection>
     */
    public function getSections(): array
    {
        if ($this->sections === null) {
            $this->sections = $this->sectionArranger->arrange(
                $this->visibleItems(),
                $this->readGroups(),
                $this->readMaxItemsPerColumn(),
                (string) $this->getNameInLayout()
            );
        }

        return $this->sections;
    }

    /**
     * Check whether at least one section is a titled group, so the menu renders as groups rather than one list
     *
     * @return bool
     */
    public function isGrouped(): bool
    {
        foreach ($this->getSections() as $section) {
            if ($section->getGroup() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the HTML id of a group's heading, unique per menu block and group
     *
     * @param MenuGroupInterface $group
     * @return string
     */
    public function getGroupHeadingId(MenuGroupInterface $group): string
    {
        return 'hryvinskyi-core-menu-' . $this->toIdPart((string) $this->getNameInLayout())
            . '-' . $this->toIdPart($group->getCode());
    }

    /**
     * Get the allowed items keyed by item name, sorted by sort order; built once per block
     *
     * @return array<int|string, MenuItemInterface>
     */
    private function allowedItems(): array
    {
        if ($this->sortedItems !== null) {
            return $this->sortedItems;
        }

        $items = [];
        foreach ($this->readItemsConfig() as $name => $itemData) {
            $item = $this->createItem((string) $name, $itemData);
            if ($item !== null && $this->isAllowed($item)) {
                $items[$name] = $item;
            }
        }

        $this->sortedItems = $this->sortItems($items);

        return $this->sortedItems;
    }

    /**
     * Get the items the menu shows: allowed and active, keyed by item name, in display order
     *
     * @return array<int|string, MenuItemInterface>
     */
    private function visibleItems(): array
    {
        return array_filter($this->allowedItems(), fn (MenuItemInterface $item): bool => $item->isActive());
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
     * Read the "groups" layout argument; a malformed group is logged and left out
     *
     * @return list<MenuGroupInterface>
     */
    private function readGroups(): array
    {
        $groupsConfig = $this->getData('groups');

        if ($groupsConfig === null) {
            return [];
        }

        if (!is_array($groupsConfig)) {
            $this->_logger->warning(
                'Admin menu block ignores its "groups" argument: an array is expected.',
                ['block' => $this->getNameInLayout(), 'given' => get_debug_type($groupsConfig)]
            );

            return [];
        }

        $groups = [];
        foreach ($groupsConfig as $code => $groupData) {
            $group = $this->createGroup((string) $code, $groupData);
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Build one menu group, or log and return null when its configuration is malformed
     *
     * @param string $code
     * @param mixed $groupData
     * @return MenuGroupInterface|null
     */
    private function createGroup(string $code, mixed $groupData): ?MenuGroupInterface
    {
        if (!is_array($groupData)) {
            $this->logSkippedGroup($code, 'the group configuration must be an array');

            return null;
        }

        $data = [];
        foreach ($groupData as $key => $value) {
            $data[(string) $key] = $value;
        }

        try {
            return $this->menuGroupFactory->create($code, $data);
        } catch (\InvalidArgumentException $exception) {
            $this->logSkippedGroup($code, $exception->getMessage());

            return null;
        }
    }

    /**
     * Read the "max_items_per_column" layout argument
     *
     * A positive whole number is used as given; anything else falls back to the default, with a warning when a
     * value was given.
     *
     * @return int
     */
    private function readMaxItemsPerColumn(): int
    {
        $value = $this->getData('max_items_per_column');

        if ($value === null) {
            return self::DEFAULT_MAX_ITEMS_PER_COLUMN;
        }

        $limit = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (is_int($limit)) {
            return $limit;
        }

        $this->_logger->warning(
            'Admin menu block ignores its "max_items_per_column" argument: a positive whole number is expected.',
            ['block' => $this->getNameInLayout(), 'given' => is_scalar($value) ? $value : get_debug_type($value)]
        );

        return self::DEFAULT_MAX_ITEMS_PER_COLUMN;
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
     * Log a menu group left out because of its configuration
     *
     * @param string $code
     * @param string $reason
     * @return void
     */
    private function logSkippedGroup(string $code, string $reason): void
    {
        $this->_logger->warning(
            'Admin menu group skipped: ' . $reason,
            ['block' => $this->getNameInLayout(), 'group' => $code]
        );
    }

    /**
     * Sort menu items by sort order, keeping their names; items with the same sort order keep their configured order
     *
     * @param array<int|string,MenuItemInterface> $items
     * @return array<int|string, MenuItemInterface>
     */
    private function sortItems(array $items): array
    {
        uasort($items, function (MenuItemInterface $a, MenuItemInterface $b): int {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        return $items;
    }

    /**
     * Turn a name into a part of an HTML id: letters, digits, "_" and "-" only
     *
     * @param string $name
     * @return string
     */
    private function toIdPart(string $name): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-');
    }
}
