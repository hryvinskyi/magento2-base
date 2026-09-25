<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Base\Api\Menu;

/**
 * Factory interface for creating menu items from array configuration
 *
 * @api
 */
interface MenuItemFactoryInterface
{
    /**
     * Create menu item from array configuration
     *
     * Known keys: label, route, route_params, sort_order, is_active, class, icon, resource.
     * A key that is present with a value of the wrong type is rejected rather than coerced.
     *
     * @param array<string,mixed> $data
     * @return MenuItemInterface
     * @throws \InvalidArgumentException When a known key holds a value of the wrong type
     */
    public function create(array $data): MenuItemInterface;
}
