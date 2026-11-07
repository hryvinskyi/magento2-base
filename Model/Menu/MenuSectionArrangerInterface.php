<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Model\Menu;

use Hryvinskyi\Base\Api\Menu\MenuGroupInterface;
use Hryvinskyi\Base\Api\Menu\MenuItemInterface;

/**
 * Lays out the visible items of an admin menu as sections (groups) of columns
 */
interface MenuSectionArrangerInterface
{
    /**
     * Arrange the items into sections
     *
     * Declared groups are listed by sort order (ties keep the declared order), each with its items in the order
     * given; a group without items has no section. Items naming no group, or a group that is not declared, follow
     * in a trailing untitled section; an undeclared code is logged as a warning. A section longer than the column
     * limit is split into ceil(n / limit) columns whose sizes differ by at most one, the earlier columns taking
     * the extra items. A menu where no item lands in a group is a single untitled section with a single column,
     * as a menu without groups always was.
     *
     * @param array<int|string,MenuItemInterface> $items Visible items in display order, keyed by item name
     * @param list<MenuGroupInterface> $groups Declared groups, in declared order
     * @param int $maxItemsPerColumn Column limit, at least 1
     * @param string $menuName Name of the menu block, for the log
     * @return list<MenuSection>
     * @throws \InvalidArgumentException When the column limit is below 1
     */
    public function arrange(array $items, array $groups, int $maxItemsPerColumn, string $menuName): array;
}
