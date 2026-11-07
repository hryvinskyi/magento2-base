<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * A menu entry that names the group it is listed in
 *
 * The admin menu block lists such an item inside the group whose code it names, when the menu declares that
 * group. An item that implements only MenuItemInterface names no group, so existing implementations keep working
 * and are listed with the ungrouped items, after every group.
 *
 * @api
 */
interface GroupedMenuItemInterface extends MenuItemInterface
{
    /**
     * Get the code of the group the item is listed in
     *
     * An empty string means the item names no group.
     *
     * @return string
     */
    public function getGroup(): string;
}
